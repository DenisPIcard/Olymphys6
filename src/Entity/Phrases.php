<?php

namespace App\Entity;

use App\Repository\PhrasesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhrasesRepository::class)]
class Phrases
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable:true)]
    #[Assert\Length(min: 1, minMessage: 'La phrase amusante doit contenir au moins {{ limit }} caractère.') ]
    private ?string $phrase = null;

    #[ORM\ManyToOne]
    private ?Liaison $liaison = null;


    #[ORM\Column(type: Types::TEXT, nullable:true)]
    #[Assert\Length(min: 1, minMessage: 'L \'intitullé amusant doit contenir au moins {{ limit }} caractère.') ]
    private ?string $prix = null;

    #[ORM\ManyToOne(inversedBy: 'phrases')]
    private ?Equipes $equipe = null;

    #[ORM\ManyToOne(targetEntity: Jures::class, inversedBy: 'phrases')]
    private ?Jures $jure;

     /**
     * @return string|null
     */
    public function __toString() : string
    {
        if (($this->phrase!==null)and ($this->liaison!== null)and ($this->prix !==null)){
            return $this->phrase . $this->liaison->getLiaison() . $this->prix;
        }
        else{
            return '';
        }
    }
    /**
     * Get id
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set equipe
     *
     * @param Equipes|null $equipe
     *
     * @return Phrases
     */
    public function setEquipe(?Equipes $equipe): Phrases
    {
        $this->equipe = $equipe;

        return $this;
    }

    /**
     * Get equipe
     *
     * @return Equipes|null
     */
    public function getEquipe(): ?Equipes
    {
        return $this->equipe;
    }


    /**
     * Get phrase
     *
     * @return string|null
     */
    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    /**
     * Set phrase
     *
     * @param string $phrase
     *
     * @return Phrases
     */
    public function setPhrase(?string $phrase): Phrases
    {
        $this->phrase = $phrase;

        return $this;
    }

    public function getLiaison(): ?Liaison
    {
        return $this->liaison;
    }

    public function setLiaison(Liaison $liaison = null): void
    {
        $this->liaison = $liaison;
    }

    /**
     * Get prix
     *
     * @return string|null
     */
    public function getPrix(): ?string
    {
        return $this->prix;
    }

    /**
     * Set prix
     *
     * @param string $prix
     *
     * @return Phrases
     */
    public function setPrix(string $prix): Phrases
    {
        $this->prix = $prix;

        return $this;
    }

    public function getJure(): ?Jures
    {
        return $this->jure;
    }

    public function setJure(?Jures $jure): self
    {
        $this->jure = $jure;

        return $this;
    }

}