<?php

namespace App\Controller;

use App\Entity\Food;
use App\Repository\FoodRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route as AnnotationRoute;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/food', name: 'app_api_food_')]
final class FoodController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private FoodRepository $repository)
    {
    }

   #[Route(methods: 'POST')]
   #[OA\Post(
    path: '/api/food',
    summary: 'Créer un plat',
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Données du plat à créer',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Tarte aux pommes'),
                new OA\Property(property: 'description', type: 'string', example: 'Délicieux dessert maison'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 12.5),
                new OA\Property(property: 'categoryId', type: 'integer', example: 3) // si relation avec Category
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Plat créé avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 10),
                    new OA\Property(property: 'title', type: 'string', example: 'Tarte aux pommes'),
                    new OA\Property(property: 'description', type: 'string', example: 'Délicieux dessert maison'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 12.5),
                    new OA\Property(property: 'uuid', type: 'string', format: 'uuid', example: 'a6b7a3c2-cc38-4f26-8e3c-3285f174c765'),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                ]
            )
        )
    ]
)]

    public function new(): Response
    {
        $food = new Food();
        $food->setTitle('Salade grec');
        $food->setDescription('salade grec composée de concombre, de tomate et de feta.');
        $food->setPrice('25');
        $food->setCreatedAt(new DateTimeImmutable());

        // Tell Doctrine you want to (eventually) save the restaurant (no queries yet)
        $this->manager->persist($food);
        // Actually executes the queries (i.e. the INSERT query)
        $this->manager->flush();

        return $this->json(
            ['message' => "Food resource created with {$food->getId()} id"],
            Response::HTTP_CREATED,
        );
    }

   #[Route('/{id}', name: 'show', methods: 'GET')]
   #[OA\Get(
    path: '/api/food/{id}',
    summary: 'Afficher les plats du restaurant',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID des plats à afficher'
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Plat trouvé avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 8),
                    new OA\Property(property: 'name', type: 'string', example: 'tarte au concombre'),
                    new OA\Property(property: 'description', type: 'string', example: 'Super plat'),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Plat non trouvé'
        )
    ]
)]
    public function show(string $id): Response
    {
        $food = $this->repository->findOneBy(['id' => $id]);

        if (!$food) {
            throw $this->createNotFoundException("No food found for {$id} id");
        }

        return $this->json(
            ['message' => "A food was found : {$food->getTitle()} for {$food->getPrice()} euros, and {$food->getCategory()}"]
        );
    }
    
   #[Route('/{id}', name: 'edit', methods: 'PUT')]
   #[OA\Put(
    path: '/api/food/{id}',
    summary: 'Modifier un plat existant',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID du plat à modifier'
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Données mises à jour du plat',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Tarte au brocoli'),
                new OA\Property(property: 'description', type: 'string', example: 'Un plat très sain')
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Plat modifié avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 8),
                    new OA\Property(property: 'name', type: 'string', example: 'Tarte au brocoli'),
                    new OA\Property(property: 'description', type: 'string', example: 'Un plat très sain'),
                    new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Plat non trouvé'
        )
    ]
)]

    public function edit(int $id): Response
    {
        $food = $this->repository->findOneBy(['id' => $id]);

        if (!$food) {
            throw $this->createNotFoundException("No food found for {$id} id");
        }

        $food->setName('Restaurant name updated');
        $this->manager->flush();

        return $this->redirectToRoute('app_api_food_show', ['id' => $food->getId()]);
    }

    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    #[OA\Delete(
    path: '/api/food/{id}',
    summary: 'Supprimer un plat',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID du plat à supprimer'
        )
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: 'Plat supprimé avec succès'
        ),
        new OA\Response(
            response: 404,
            description: 'Plat non trouvé'
        )
    ]
)]

    public function delete(int $id): Response
    {
        $food = $this->repository->findOneBy(['id' => $id]);
        if (!$food) {
            throw $this->createNotFoundException("No food found for {$id} id");
        }

        $this->manager->remove($food);
        $this->manager->flush();

        return $this->json(['message' => "food resource deleted"], Response::HTTP_NO_CONTENT);
    }
}
