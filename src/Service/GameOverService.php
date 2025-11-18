<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GameOverService
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository
    ) {}

    /**
     * Vérifie si l'utilisateur actuel a perdu (gold < 0)
     * Retourne true si l'utilisateur a perdu, false sinon
     */
    public function checkGameOver(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return false;
        }

        $user = $token->getUser();

        if (!$user || !is_object($user) || !method_exists($user, 'getUserIdentifier')) {
            return false;
        }

        $userEntity = $this->userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$userEntity) {
            return false;
        }

        // Les administrateurs ne peuvent pas perdre
        if (in_array('ROLE_ADMIN', $userEntity->getRoles(), true)) {
            return false;
        }

        $gold = $userEntity->getGold() ?? 0;
        return $gold < 0;
    }

    /**
     * Vérifie si un utilisateur spécifique a perdu (gold < 0)
     */
    public function isUserGameOver(User $user): bool
    {
        // Les administrateurs ne peuvent pas perdre
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        $gold = $user->getGold() ?? 0;
        return $gold < 0;
    }
}
