<?php

namespace App\Entity;

use App\Repository\HamsterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: HamsterRepository::class)]
class Hamster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['hamster', 'owner'])]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    #[Groups(['hamster', 'owner'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['hamster', 'owner'])]
    private ?int $hunger = null;

    #[ORM\Column]
    #[Groups(['hamster', 'owner'])]
    private ?int $age = null;

    #[ORM\Column(length: 1)]
    #[Groups(['hamster', 'owner'])]
    private ?string $genre = null;

    #[ORM\Column]
    #[Groups(['hamster', 'owner'])]
    private ?bool $active = null;

    #[ORM\ManyToOne(inversedBy: 'hamsters')]
    #[Groups(['hamster'])]
    // #[Ignore]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHunger(): ?int
    {
        return $this->hunger;
    }

    public function setHunger(?int $hunger): static
    {
        $this->hunger = $hunger;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
