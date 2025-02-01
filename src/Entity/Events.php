<?php

namespace App\Entity;

use App\Repository\EventsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: EventsRepository::class)]
#[ORM\UniqueConstraint(name: "unique_external_id_source", columns: ["external_id", "source"])]
class Events
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    private string $external_id;

    #[ORM\Column(length: 255)]
    private string $source;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeEvent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    public function __construct(

        string $external_id,
        string $source,

    ) {
        $this->external_id = $external_id;
        $this->source = $source;
    }


    public function getId(): ?int
    {
        return $this->id;
    }
    public function getExternalId(): string
    {
        return $this->external_id;
    }
    public function getSource(): string
    {
        return $this->source;
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

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
    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }
}
