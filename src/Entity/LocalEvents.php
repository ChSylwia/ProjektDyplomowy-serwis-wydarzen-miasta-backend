<?php

namespace App\Entity;

use App\Repository\LocalEventsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: LocalEventsRepository::class)]
class LocalEvents
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $priceMin = null;
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $priceMax = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeEvent = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'localEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?bool $deleted = null;


    #[ORM\Column]
    private array $category = [];


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPriceMin(): ?string
    {
        return $this->priceMin;
    }
    public function getPriceMax(): ?string
    {
        return $this->priceMax;
    }
    public function setPriceMin(?string $priceMin): static
    {
        $this->priceMin = $priceMin;

        return $this;
    }
    public function setPriceMax(?string $priceMax): static
    {
        $this->priceMax = $priceMax;

        return $this;
    }
    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }
    public function getTypeEvent(): ?string
    {
        return $this->typeEvent;
    }
    public function setTypeEvent(?string $typeEvent): static
    {
        $this->typeEvent = $typeEvent;
        return $this;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }


    public function getCategory(): array
    {
        $category = $this->category;

        return array_unique($category);
    }

    public function setCategory(array $category): static
    {
        $this->category = $category;

        return $this;
    }
}
