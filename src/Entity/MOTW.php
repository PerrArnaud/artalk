<?php

namespace App\Entity;

use App\Repository\MOTWRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MOTWRepository::class)]
class MOTW
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $Date = null;

    #[ORM\Column(length: 255)]
    private ?string $Artist = null;

    #[ORM\Column(length: 255)]
    private ?string $Visual = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): static
    {
        $this->Name = $Name;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->Date;
    }

    public function setDate(\DateTime $Date): static
    {
        $this->Date = $Date;

        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->Artist;
    }

    public function setArtist(string $Artist): static
    {
        $this->Artist = $Artist;

        return $this;
    }

    public function getVisual(): ?string
    {
        return $this->Visual;
    }

    public function setVisual(string $Visual): static
    {
        $this->Visual = $Visual;

        return $this;
    }
}
