<?php

namespace App\Entity;

use App\Repository\PrixRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrixRepository::class)]
class Prix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $voix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intervenant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $remisPar = null;

    #[ORM\OneToOne(mappedBy: 'prix', cascade: ['persist', 'remove'])]
    private ?Equipes $equipe = null;

    #[ORM\Column(nullable: true)]
    private ?bool $attribue = null;

    public function __toString(): string
    {

        return $this->niveau . '-' . $this->prix;

    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function setPrix(string $prix): Prix
    {
        $this->prix = $prix;

        return $this;
    }


    public function getPrix(): ?string
    {
        return $this->prix;
    }


    public function setNiveau(string $niveau): Prix
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }


    public function getVoix(): ?string
    {
        return $this->voix;
    }

    public function setVoix(?string $voix): self
    {
        $this->voix = $voix;

        return $this;
    }

    public function getIntervenant(): ?string
    {
        return $this->intervenant;
    }

    public function setIntervenant(?string $intervenant): Prix
    {
        $this->intervenant = $intervenant;

        return $this;
    }

    public function getRemisPar(): ?string
    {
        return $this->remisPar;
    }

    public function setRemisPar(?string $remisPar): self
    {
        $this->remisPar = $remisPar;

        return $this;
    }

    public function getEquipe(): ?Equipes
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipes $equipe): self
    {
        if ($equipe === null && $this->equipe !== null) {
            $this->equipe->setPrix(null);
            $this->attribue = false;
        }

        // set the owning side of the relation if necessary
        if ($equipe !== null && $equipe->getPrix() !== $this) {
            $equipe->setPrix($this);
        }
        if ($equipe !== null) {
            $this->attribue = true;

        }


        $this->equipe = $equipe;

        return $this;
    }

    public function isAttribue(): ?bool
    {
        return $this->attribue;
    }

    public function setAttribue(?bool $attribue): static
    {
        $this->attribue = $attribue;

        return $this;
    }
}