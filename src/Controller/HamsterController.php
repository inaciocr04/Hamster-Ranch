<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Repository\HamsterRepository;
use App\Entity\Hamster;
use App\Entity\User;
use Faker\Factory;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;


final class HamsterController extends AbstractController
{
    #[Route('/api/hamsters', name: 'hamsters_user')]
    public function getHamstersUser(HamsterRepository $hamsterRepository): JsonResponse
    {
        $user = $this->getUser();
        $hamsters = $hamsterRepository->findBy(['owner' => $user]);

        return $this->json([
            "hamsters" => $hamsters,
        ], Response::HTTP_OK, [], ['groups' => 'hamster']);
    }

    #[Route('/api/hamsters/{id}', name: 'hamster_user_show')]
    public function getHamsterUserShow(HamsterRepository $hamsterRepository, $id): JsonResponse
    {
        $user = $this->getUser();
        $hamster = $hamsterRepository->findOneBy(['id' => (int)$id, 'owner' => $user]);

        if (!$hamster) {
            return $this->json([
                'error' => 'Hamster introuvable ou ne vous appartient pas'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            [
                "hamster" => $hamster,
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'hamster']
        );
    }

    #[Route('/api/hamsters/reproduce', name: 'hamsters_reproduce', methods: ['POST'])]
    public function reproduce(
        Request $request,
        HamsterRepository $hamsterRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->getUser();

        // Désérialiser les données de la requête
        $data = $serializer->deserialize($request->getContent(), ReproduceData::class, 'json');

        // Valider les données
        $errors = $validator->validate($data);
        if (count($errors) > 0) {
            return $this->json([
                'error' => 'Données invalides',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer les deux hamsters
        $hamster1 = $hamsterRepository->findOneBy(['id' => $data->idHamster1, 'owner' => $user]);
        $hamster2 = $hamsterRepository->findOneBy(['id' => $data->idHamster2, 'owner' => $user]);

        // Vérifier que les hamsters existent et appartiennent à l'utilisateur
        if (!$hamster1) {
            return $this->json([
                'error' => 'Hamster 1 introuvable ou ne vous appartient pas'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$hamster2) {
            return $this->json([
                'error' => 'Hamster 2 introuvable ou ne vous appartient pas'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que les hamsters sont de genres différents (m et f)
        $genre1 = $hamster1->getGenre();
        $genre2 = $hamster2->getGenre();

        if ($genre1 === $genre2) {
            return $this->json([
                'error' => 'Les deux hamsters doivent être de genres différents (m et f)'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($genre1, ['m', 'f']) || !in_array($genre2, ['m', 'f'])) {
            return $this->json([
                'error' => 'Les genres doivent être "m" ou "f"'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer le nouveau hamster
        $faker = Factory::create("fr_FR");
        $newHamster = new Hamster();
        $newHamster->setName($faker->firstName());
        $newHamster->setGenre(rand(0, 1) === 0 ? 'm' : 'f');
        $newHamster->setAge(0);
        $newHamster->setHunger(100);
        $newHamster->setActive(true);
        $newHamster->setOwner($user);

        $entityManager->persist($newHamster);
        $entityManager->flush();

        return $this->json($newHamster, Response::HTTP_CREATED, [], ['groups' => 'hamster']);
    }

    #[Route('/api/hamsters/{id}/feed', name: 'hamster_user_feed', methods: ['POST'])]
    public function feedHamsterUser(
        HamsterRepository $hamsterRepository,
        UserRepository $userRepository,
        $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $user = $this->getUser();
        $hamster = $hamsterRepository->findOneBy(['id' => (int)$id, 'owner' => $user]);


        $currentHunger = $hamster->getHunger() ?? 0;
        $cost = 100 - $currentHunger;

        if ($cost <= 0) {
            return $this->json([
                'error' => 'Le hamster est déjà rassasié (hunger >= 100)'
            ], Response::HTTP_BAD_REQUEST);
        }

        $gold = $userRepository->find($user)->getGold() ?? 0;

        if ($gold < $cost) {
            return $this->json([
                'error' => 'Pas assez d\'or. Coût: ' . $cost . ', Or disponible: ' . $gold
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($user);
        $gold = $gold - $cost;
        $user->setGold($gold);
        $entityManager->persist($user);
        $entityManager->flush();

        $hamster->setHunger(100);
        $entityManager->persist($hamster);
        $entityManager->flush();

        return $this->json([
            "hamster" => $hamster,
        ], Response::HTTP_OK, [], ['groups' => 'hamster']);
    }
}

class ReproduceData
{
    #[Assert\NotBlank]
    public ?int $idHamster1 = null;

    #[Assert\NotBlank]
    public ?int $idHamster2 = null;
}
