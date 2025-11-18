<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Hamster;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->createUser("user@sf.com", "password", ["ROLE_USER"]);
        $user->setGold(500);
        $manager->persist($user);

        for ($i = 0; $i < 4; $i++) {
            $hamster = $this->createHamster($user, $i);
            $manager->persist($hamster);
        }

        $admin = $this->createUser("admin@sf.com", "password", ["ROLE_ADMIN"]);
        $admin->setGold(500);
        $manager->persist($admin);

        for ($i = 0; $i < 4; $i++) {
            $hamster = $this->createHamster($admin, $i);
            $manager->persist($hamster);
        }

        $manager->flush();
    }

    public function createUser(String $email, String $password, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        return $user;
    }

    public function createHamster(User $user, int $index): Hamster
    {
        $faker = Factory::create("fr_FR");
        $genres = ['m', 'm', 'f', 'f'];
        $genre = $genres[$index];

        $age = rand(0, 500);

        $hamster = new Hamster();
        $hamster->setName($faker->firstName());
        $hamster->setGenre($genre);
        $hamster->setAge($age);
        $hamster->setHunger(rand(0, 100));
        $hamster->setActive($age <= 500 ? true : false);
        $hamster->setOwner($user);

        return $hamster;
    }
}
