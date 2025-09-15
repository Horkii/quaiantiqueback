<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/restaurant', name: 'app_api_restaurant_')]
final class RestaurantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RestaurantRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('', name: 'new', methods: ['POST'])]
        #[OA\Post(
        path: '/api/restaurant',
        summary: 'Créer un restaurant',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données du restaurant à créer',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Quai Antique'),
                    new OA\Property(property: 'description', type: 'string', example: 'Description du resturant'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Restaurant créé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: '8'),
                        new OA\Property(property: 'name', type: 'string', example: 'Quai Antique'),
                        new OA\Property(property: 'description,', type: 'string', example: 'Super restaurant'),
                        new OA\Property(property: 'createdAt', type: 'string', format:'date-time' )
                    ]
                )
            )
        ]
    )]
    public function new(Request $request): JsonResponse
    {
        $restaurant = $this->serializer->deserialize(
            $request->getContent(),
            Restaurant::class,
            'json'
        );

        $restaurant->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($restaurant);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($restaurant, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
    path: '/api/restaurant/{id}',
    summary: 'Afficher un restaurant',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID du restaurant à afficher'
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Restaurant trouvé avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 8),
                    new OA\Property(property: 'name', type: 'string', example: 'Quai Antique'),
                    new OA\Property(property: 'description', type: 'string', example: 'Super restaurant'),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Restaurant non trouvé'
        )
    ]
)]

    public function show(string $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if ($restaurant) {
            $responseData = $this->serializer->serialize($restaurant, 'json');
            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
    path: '/api/restaurant/{id}',
    summary: 'Modifier un restaurant existant',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID du restaurant à modifier'
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Données mises à jour du restaurant',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Nouveau Quai Antique'),
                new OA\Property(property: 'description', type: 'string', example: 'Restaurant gastronomique rénové')
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Restaurant modifié avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 8),
                    new OA\Property(property: 'name', type: 'string', example: 'Nouveau Quai Antique'),
                    new OA\Property(property: 'description', type: 'string', example: 'Restaurant gastronomique rénové'),
                    new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Restaurant non trouvé'
        )
    ]
)]

    public function edit(string $id, Request $request): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if ($restaurant) {
            $this->serializer->deserialize(
                $request->getContent(),
                Restaurant::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $restaurant]
            );

            $restaurant->setUpdatedAt(new DateTimeImmutable());

            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
    path: '/api/restaurant/{id}',
    summary: 'Supprimer un restaurant',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID du restaurant à supprimer'
        )
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: 'Restaurant supprimé avec succès'
        ),
        new OA\Response(
            response: 404,
            description: 'Restaurant non trouvé'
        )
    ]
)]

    public function delete(string $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if ($restaurant) {
            $this->manager->remove($restaurant);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
