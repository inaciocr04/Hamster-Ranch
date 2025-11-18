<?php

namespace App\Service;

use App\Entity\Hamster;
use App\Entity\User;
use App\Repository\HamsterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;

class HamsterService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HamsterRepository $hamsterRepository
    ) {}

    
    public function createInitialHamsters(User $user): void
    {
        $faker = Factory::create("fr_FR");
        $genres = ['m', 'm', 'f', 'f'];

        for ($i = 0; $i < 4; $i++) {
            $hamster = new Hamster();
            $hamster->setName($faker->firstName());
            $hamster->setGenre($genres[$i]);
            $hamster->setAge(rand(0, 500));
            $hamster->setHunger(rand(0, 100));
            $hamster->setActive(true);
            $hamster->setOwner($user);
            $this->entityManager->persist($hamster);
        }
    }

    public function ageAllHamsters(User $user): void
    {
        $hamsters = $this->hamsterRepository->findBy(['owner' => $user]);

        foreach ($hamsters as $hamster) {
            $currentAge = $hamster->getAge() ?? 0;
            $hamster->setAge($currentAge + 5);

            $currentHunger = $hamster->getHunger() ?? 0;
            $newHunger = max(0, $currentHunger - 5);
            $hamster->setHunger($newHunger);

            $this->entityManager->persist($hamster);
        }
    }
}
