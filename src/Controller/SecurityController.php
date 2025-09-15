<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api', name: 'app_api_')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer
    ) {
    }

    /**REGISTRATION */

    #[Route('/registration', name: 'registration', methods: ['POST'])]
    #[OA\Post(
        path: '/api/registration',
        summary: 'Inscription d\'un nouvel utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données de l\'utilisateur à inscrire',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'prénom'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'nom'),
                    new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'mot de passe')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'adresse@email.com'),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(
                            property: 'roles', 
                            type: 'array', 
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        )
                    ]
                )
            )
        ]
    )]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            [
                'user' => $user->getUserIdentifier(),
                'apiToken' => $user->getApiToken(),
                'roles' => $user->getRoles(),
            ],
            Response::HTTP_CREATED
        );
    }

    /**LOGIN */

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
    path: '/api/login',
    summary: 'Connecter un utilisateur',
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Données de l’utilisateur pour se connecter',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'username', type: 'string', example: 'adresse@email.com'),
                new OA\Property(property: 'password', type: 'string', example: 'Mot de passe')
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Connexion réussie',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'user', type: 'string', example: 'Nom d\'utilisateur'),
                    new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'string', example: 'ROLE_USER')
                    )
                ]
            )
        )
    ]
)]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Identifiants manquants'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**METHOD ME */

    #[Route('/me/{id}', name: 'me', methods: ['GET'])]
    #[OA\Get(
    path: '/api/me/{id}',
    summary: 'Récupérer les informations d\'un utilisateur',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de l\'utilisateur à récupérer',
            schema: new OA\Schema(type: 'integer')
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Utilisateur trouvé',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'email', type: 'string', example: 'user@email.com'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Utilisateur non trouvé'
        )
    ]
)]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        // Utilise les groupes de sérialisation si configurés
        $data = $this->serializer->serialize($user, 'json', ['groups' => ['user:read']]);

        return new JsonResponse($data, 200, [], true); // true = JSON déjà encodé
    }

    /**METHOD ME/EDIT */

    #[Route('/me/edit/{id}', name: 'edit_profile', methods: ['PUT'])]
    #[OA\Put(
    path: '/api/me/edit/{id}',
    summary: 'Modifier les informations d\'un utilisateur',
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Champs à mettre à jour',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'nouveau@email.com'),
                new OA\Property(property: 'password', type: 'string', example: 'NouveauMdp123'),
                new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                new OA\Property(property: 'lastName', type: 'string', example: 'Dupont')
            ]
        )
    ),
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de l\'utilisateur à modifier',
            schema: new OA\Schema(type: 'integer')
        )
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: 'Utilisateur modifié avec succès'
        ),
        new OA\Response(
            response: 404,
            description: 'Utilisateur non trouvé'
        )
    ]
)]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(
        #[CurrentUser] User $user,
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = $request->getContent();
        $decoded = json_decode($data, true);

        // Met à jour les champs sauf le mot de passe
        $this->serializer->deserialize($data, User::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $user,
            'groups' => ['user:write'],
        ]);

        // Mise à jour du mot de passe si présent
        if (!empty($decoded['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $decoded['password']);
            $user->setPassword($hashedPassword);
        }

        // Met à jour la date de modification
        $user->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
