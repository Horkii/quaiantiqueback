<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route as AnnotationRoute;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/category', name: 'app_api_category_')]
final class CategoryController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private CategoryRepository $repository)
    {
    }

   #[Route(methods: 'POST')]
   #[OA\Post(
    path: '/api/category',
    summary: 'Créer une catégorie de plat',
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Données de la catégorie à créer',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Plats principaux'),
                new OA\Property(property: 'description', type: 'string', example: 'Catégorie contenant les plats chauds')
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Catégorie créée avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 5),
                    new OA\Property(property: 'title', type: 'string', example: 'Plats principaux'),
                    new OA\Property(property: 'description', type: 'string', example: 'Catégorie contenant les plats chauds'),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                ]
            )
        )
    ]
)]

    public function new(): Response
    {
        $category = new category();
        $category->setTitle('Plat');
        $category->setCreatedAt(new DateTimeImmutable());

        // Tell Doctrine you want to (eventually) save the restaurant (no queries yet)
        $this->manager->persist($category);
        // Actually executes the queries (i.e. the INSERT query)
        $this->manager->flush();

        return $this->json(
            ['message' => "category resource created with {$category->getId()} id"],
            Response::HTTP_CREATED,
        );
    }

   #[Route('/{id}', name: 'show', methods: 'GET')]
   #[OA\Get(
    path: '/api/category/{id}',
    summary: 'Afficher les categories des plats du restaurant',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID des catégories à afficher'
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
        $category = $this->repository->findOneBy(['id' => $id]);

        if (!$category) {
            throw $this->createNotFoundException("No category found for {$id} id");
        }

        return $this->json(
            ['message' => "A category was found : {$category->getTitle()}"]
        );
    }
    
   #[Route('/{id}', name: 'edit', methods: 'PUT')]
   #[OA\Put(
    path: '/api/category/{id}',
    summary: 'Modifier une catégorie de plat',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la catégorie à modifier'
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Données mises à jour de la catégorie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Entrées revisitées'),
                new OA\Property(property: 'description', type: 'string', example: 'Catégorie pour les entrées froides et chaudes')
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Catégorie modifiée avec succès',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 8),
                    new OA\Property(property: 'name', type: 'string', example: 'Entrées revisitées'),
                    new OA\Property(property: 'description', type: 'string', example: 'Catégorie pour les entrées froides et chaudes'),
                    new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Catégorie non trouvée'
        )
    ]
)]

    public function edit(int $id): Response
    {
        $category = $this->repository->findOneBy(['id' => $id]);

        if (!$category) {
            throw $this->createNotFoundException("No category found for {$id} id");
        }

        $category->setName('category name updated');
        $this->manager->flush();

        return $this->redirectToRoute('app_api_category_show', ['id' => $category->getId()]);
    }

    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    #[OA\Delete(
    path: '/api/category/{id}',
    summary: 'Supprimer une catégorie de plat',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la catégorie à supprimer'
        )
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: 'Catégorie supprimée avec succès'
        ),
        new OA\Response(
            response: 404,
            description: 'Catégorie non trouvée'
        )
    ]
)]

    public function delete(int $id): Response
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if (!$category) {
            throw $this->createNotFoundException("No category found for {$id} id");
        }

        $this->manager->remove($category);
        $this->manager->flush();

        return $this->json(['message' => "category resource deleted"], Response::HTTP_NO_CONTENT);
    }
}
