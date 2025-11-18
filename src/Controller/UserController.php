<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\HamsterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/api/user', name: 'api_user', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->json(
            ['user' => $user],
            Response::HTTP_OK,
            [],
            ['groups' => 'user']
        );
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        HamsterService $hamsterService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(
                ['error' => 'Données JSON invalides'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(
                ['error' => 'Les champs email et password sont requis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(
                ['error' => 'Format d\'email invalide'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (strlen($password) < 6) {
            return $this->json(
                ['error' => 'Le mot de passe doit contenir au moins 6 caractères'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(
                ['error' => 'Cet email est déjà utilisé'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);
        $user->setGold(500);

        $entityManager->persist($user);
        $hamsterService->createInitialHamsters($user);
        $entityManager->flush();

        return $this->json(
            [
                'user' => $user,
                'hamsters' => $user->getHamsters()->toArray()
            ],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'user']
        );
    }

    #[Route('/api/delete/{id}', name: 'api_delete_user', methods: ['DELETE'])]
    public function deleteUser(
        $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(
                ['error' => 'Accès refusé. ROLE_ADMIN requis'],
                Response::HTTP_FORBIDDEN
            );
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(
            ['message' => 'Utilisateur et ses hamsters supprimés avec succès'],
            Response::HTTP_OK
        );
    }
}
