<?php

namespace App\Controller;

use App\Entity\Hamster;
use App\Repository\HamsterRepository;
use App\Repository\UserRepository;
use App\Service\GameOverService;
use App\Service\HamsterService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HamsterController extends AbstractController
{
    public function __construct(
        private GameOverService $gameOverService
    ) {}
    #[Route('/api/hamsters', name: 'hamsters_user', methods: ['GET'])]
    public function getHamstersUser(HamsterRepository $hamsterRepository): JsonResponse
    {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $hamsters = $hamsterRepository->findBy(['owner' => $user]);

        return $this->json(
            ['hamsters' => $hamsters],
            Response::HTTP_OK,
            [],
            ['groups' => 'hamster']
        );
    }

    #[Route('/api/hamsters/{id}', name: 'hamster_user_show', methods: ['GET'])]
    public function getHamsterUserShow(HamsterRepository $hamsterRepository, $id): JsonResponse
    {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $hamster = $hamsterRepository->findOneBy(['id' => $id, 'owner' => $user]);

        if (!$hamster) {
            return $this->json(
                ['error' => 'Hamster introuvable ou ne vous appartient pas'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            ['hamster' => $hamster],
            Response::HTTP_OK,
            [],
            ['groups' => 'hamster']
        );
    }

    #[Route('/api/hamsters/reproduce', name: 'hamsters_user_reproduce', methods: ['POST'])]
    public function hamstersUserReproduce(
        Request $request,
        HamsterRepository $hamsterRepository,
        EntityManagerInterface $entityManager,
        HamsterService $hamsterService
    ): JsonResponse {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $data = $request->toArray();
        $parent1Id = $data['idHamster1'] ?? null;
        $parent2Id = $data['idHamster2'] ?? null;

        if (!$parent1Id || !$parent2Id) {
            return $this->json(
                ['error' => 'Les champs idHamster1 et idHamster2 sont requis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $parent1 = $hamsterRepository->findOneBy(['id' => $parent1Id, 'owner' => $user]);
        $parent2 = $hamsterRepository->findOneBy(['id' => $parent2Id, 'owner' => $user]);

        if (!$parent1 || !$parent2) {
            return $this->json(
                ['error' => 'Un ou plusieurs hamsters introuvables ou ne vous appartiennent pas'],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($parent1->getGenre() === $parent2->getGenre()) {
            return $this->json(
                ['error' => 'Les deux hamsters doivent être de genres différents (m et f)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$parent1->isActive() || !$parent2->isActive()) {
            return $this->json(
                ['error' => 'Les deux hamsters doivent être actifs pour se reproduire'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $faker = Factory::create('fr_FR');
        $newHamster = new Hamster();
        $newHamster->setName($faker->firstName());
        $newHamster->setGenre(rand(0, 1) === 0 ? 'm' : 'f');
        $newHamster->setAge(0);
        $newHamster->setHunger(100);
        $newHamster->setActive(true);
        $newHamster->setOwner($user);

        $entityManager->persist($newHamster);
        $hamsterService->ageAllHamsters($user);
        $entityManager->flush();

        return $this->json(
            ['hamster' => $newHamster],
            Response::HTTP_CREATED,
            [],
            ['groups' => 'hamster']
        );
    }

    #[Route('/api/hamsters/{id}/feed', name: 'hamster_user_feed', methods: ['POST'])]
    public function feedHamsterUser(
        HamsterRepository $hamsterRepository,
        UserRepository $userRepository,
        $id,
        EntityManagerInterface $entityManager,
        HamsterService $hamsterService
    ): JsonResponse {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $hamster = $hamsterRepository->findOneBy(['id' => $id, 'owner' => $user]);

        if (!$hamster) {
            return $this->json(
                ['error' => 'Hamster introuvable ou ne vous appartient pas'],
                Response::HTTP_NOT_FOUND
            );
        }

        $currentHunger = $hamster->getHunger() ?? 0;
        $cost = 100 - $currentHunger;

        if ($cost <= 0) {
            return $this->json(
                ['error' => 'Le hamster est déjà rassasié (hunger >= 100)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $userEntity = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        if (!$userEntity) {
            return $this->json(
                ['error' => 'Utilisateur introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $gold = $userEntity->getGold() ?? 0;

        // Permettre que le gold devienne négatif (cela déclenchera la fin de jeu)
        $hamsterService->ageAllHamsters($userEntity);
        $userEntity->setGold($gold - $cost);
        $hamster->setHunger(100);

        $entityManager->persist($userEntity);
        $entityManager->persist($hamster);
        $entityManager->flush();

        return $this->json(
            ['hamster' => $hamster],
            Response::HTTP_OK,
            [],
            ['groups' => 'hamster']
        );
    }

    #[Route('/api/hamsters/{id}/sell', name: 'hamster_user_sell', methods: ['POST'])]
    public function sellHamster(
        HamsterRepository $hamsterRepository,
        UserRepository $userRepository,
        $id,
        EntityManagerInterface $entityManager,
        HamsterService $hamsterService,
    ): JsonResponse {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $hamster = $hamsterRepository->findOneBy(['id' => $id, 'owner' => $user]);

        if (!$hamster) {
            return $this->json(
                ['error' => 'Hamster introuvable ou ne vous appartient pas'],
                Response::HTTP_NOT_FOUND
            );
        }

        $user = $userRepository->find($user);
        $currentGold = $user->getGold() ?? 0;
        $user->setGold($currentGold + 300);
        $entityManager->persist($user);

        $hamsterService->ageAllHamsters($user);

        $entityManager->remove($hamster);
        $entityManager->flush();

        return $this->json(
            [
                'message' => 'Hamster vendu avec succès pour 300 gold',
                'gold' => $user->getGold()
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/api/hamsters/{id}/rename', name: 'hamster_user_rename', methods: ['PUT'])]
    public function renameHamster(
        Request $request,
        HamsterRepository $hamsterRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        $id
    ): JsonResponse {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $hamster = $hamsterRepository->find($id);

        if (!$hamster) {
            return $this->json(
                ['error' => 'Hamster introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $hamsterOwner = $hamster->getOwner();
        $userEntity = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        if (!$isAdmin && (!$hamsterOwner || !$userEntity || $hamsterOwner->getId() !== $userEntity->getId())) {
            return $this->json(
                ['error' => 'Vous n\'avez pas la permission de modifier ce hamster'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);
        $newName = $data['name'] ?? null;

        if (!$newName || empty(trim($newName))) {
            return $this->json(
                ['error' => 'Le nom est requis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $hamster->setName(trim($newName));
        $entityManager->persist($hamster);
        $entityManager->flush();

        return $this->json(
            ['hamster' => $hamster],
            Response::HTTP_OK,
            [],
            ['groups' => 'hamster']
        );
    }

    #[Route('/api/hamsters/sleep/{nbDays}', name: 'hamster_sleep', methods: ['POST'])]
    public function sleep(
        HamsterRepository $hamsterRepository,
        EntityManagerInterface $entityManager,
        int $nbDays
    ): JsonResponse {
        if ($this->gameOverService->checkGameOver()) {
            return $this->json(
                ['error' => 'Vous avez perdu ! Votre solde d\'or est négatif. Le jeu est terminé pour vous.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($nbDays <= 0) {
            return $this->json(
                ['error' => 'Le nombre de jours doit être supérieur à 0'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $hamsters = $hamsterRepository->findBy(['owner' => $user]);

        if (empty($hamsters)) {
            return $this->json(
                ['message' => 'Aucun hamster trouvé pour cet utilisateur'],
                Response::HTTP_OK
            );
        }

        foreach ($hamsters as $hamster) {
            $currentAge = $hamster->getAge() ?? 0;
            $hamster->setAge($currentAge + $nbDays);

            $currentHunger = $hamster->getHunger() ?? 0;
            $newHunger = max(0, $currentHunger - $nbDays);
            $hamster->setHunger($newHunger);

            $entityManager->persist($hamster);
        }

        $entityManager->flush();

        return $this->json(
            [
                'message' => "Tous les hamsters ont vieilli de {$nbDays} jour(s)",
                'hamsters' => $hamsters,
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'hamster']
        );
    }
}
