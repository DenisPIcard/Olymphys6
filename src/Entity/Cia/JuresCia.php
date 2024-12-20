<?php

namespace App\Entity\Cia;

use App\Entity\Centrescia;
use App\Entity\Equipesadmin;
use App\Entity\User;
use App\Repository\Cia\JuresCiaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: JuresCiaRepository::class)]
class JuresCia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    private ?User $iduser;

    #[ORM\Column(name: 'prenomJure', length: 255, nullable: true)]
    private ?string $prenomJure = null;

    #[ORM\Column(name: 'nomJure', length: 255, nullable: true)]
    private ?string $nomJure = null;

    #[ORM\Column(name: 'initialesJure', length: 4, nullable: true)]
    private ?string $initialesJure = null;

    /**
     * @var Collection<int, NotesCia>
     */
    #[ORM\OneToMany(mappedBy: "jure", targetEntity: NotesCia::class)]
    private ?Collection $notesj;


    #[ORM\ManyToOne]
    private ?Centrescia $centrecia = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]//Tableau contenant le n° des  équipes pour lesquelles le jurés est rapporteur
    private ?array $rapporteur = [];

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $numJury = 1;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $lecteur = [];

    /**
     * @var Collection<int, Equipesadmin>
     */
    #[ORM\ManyToMany(targetEntity: Equipesadmin::class)]
    private Collection $equipes;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->notesj = new ArrayCollection();
        $this->equipes = new ArrayCollection();


    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get initialesJure
     *
     * @return string|null
     */
    public function getInitialesJure(): ?string
    {
        return $this->initialesJure;
    }

    /**
     * Set initialesJure
     *
     * @param string $initialesJure
     *
     * @return JuresCia
     */
    public function setInitialesJure(string $initialesJure): JuresCia
    {
        $this->initialesJure = $initialesJure;

        return $this;
    }


    /**
     * @return Collection<int, NotesCia>
     */
    public function getNotesj(): ArrayCollection|Collection|null
    {
        return $this->notesj;
    }

    public function addNotesj(NotesCia $noteCia): static
    {
        if (!$this->notesj->contains($noteCia)) {
            $this->notesj->add($noteCia);
            $noteCia->setJure($this);
        }

        return $this;
    }
    public function getNom(): string
    {
        return $this->getNomJure() . ' ' . $this->getPrenomJure();
    }

    /**
     * Get nomJure
     *
     * @return string|null
     */
    public function getNomJure(): ?string
    {
        return $this->nomJure;
    }

    /**
     * Set nomJure
     *
     * @param string|null $nomJure
     *
     * @return JuresCia
     */
    public function setNomJure(?string $nomJure): JuresCia
    {
        $this->nomJure = $nomJure;

        return $this;
    }

    /**
     * Get prenomJure
     *
     * @return string|null
     */
    public function getPrenomJure(): ?string
    {
        return $this->prenomJure;
    }

    /**
     * Set prenomJure
     *
     * @param string|null $prenomJure
     *
     * @return JuresCia
     */
    public function setPrenomJure(?string $prenomJure): JuresCia
    {
        $this->prenomJure = $prenomJure;

        return $this;
    }

    public function getIduser(): ?user
    {
        return $this->iduser;
    }

    public function setIduser(?user $iduser): self
    {
        $this->iduser = $iduser;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection|null
     */
    public function getEquipes(): ArrayCollection|Collection|null
    {
        return $this->equipes;
    }

    public function addEquipe(equipesadmin $equipe): self
    {
        if (!$this->equipes->contains($equipe)) {
            $this->equipes[] = $equipe;

            //On relie l'équipe à "une ligne note"

        }

        return $this;
    }

    public function removeEquipe(equipesadmin $equipe): self
    {
        $this->equipes->removeElement($equipe);

        return $this;
    }

    public function getCentrecia(): ?Centrescia
    {
        return $this->centrecia;
    }

    public function setCentrecia(?Centrescia $centrecia): self
    {
        $this->centrecia = $centrecia;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getRapporteur(): ?array
    {
        return $this->rapporteur;
    }

    public function setRapporteur(?array $rapporteur): self
    {
        $this->rapporteur = $rapporteur;

        return $this;
    }

    public function removeNote(NotesCia $note): self
    {
        $this->equipes->removeElement($note);

        return $this;
    }

    public function getNumJury(): ?int
    {
        return $this->numJury;
    }

    public function setNumJury(?int $numJury): self
    {
        $this->numJury = $numJury;

        return $this;
    }

    public function getLecteur(): ?array
    {
        return $this->lecteur;
    }

    public function setLecteur(?array $lecteur): static
    {
        $this->lecteur = $lecteur;

        return $this;
    }


    public function removeNotesCia(NotesCia $notesCia): static
    {
        if ($this->notesCias->removeElement($notesCia)) {
            // set the owning side to null (unless already changed)
            if ($notesCia->getJure() === $this) {
                $notesCia->setJure(null);
            }
        }

        return $this;
    }

}