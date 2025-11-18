<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'];
        $password = $data['password'];

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json([
                'error' => 'Cet email est déjà utilisé'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);
        $user->setGold(500);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'user' => $user,
            'hamsters' => $user->getHamsters()->toArray()
        ], Response::HTTP_CREATED, [], ['groups' => 'user']);
    }

    #[Route('/api/delete/{id}', name: 'api_delete_user', methods: ['DELETE'])]
    public function deleteUser(
        $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier que l'utilisateur est admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Accès refusé. ROLE_ADMIN requis'
            ], Response::HTTP_FORBIDDEN);
        }

        $user = $userRepository->find((int)$id);

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // La suppression en cascade des hamsters sera gérée automatiquement par Doctrine
        // grâce à la relation OneToMany avec cascade: ['remove'] ou orphanRemoval
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur et ses hamsters supprimés avec succès'
        ], Response::HTTP_OK);
    }
}
