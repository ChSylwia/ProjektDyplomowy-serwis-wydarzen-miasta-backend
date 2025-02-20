<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ActiveEventRepository;

/**
 * @ORM\Entity(repositoryClass=ActiveEventRepository::class)
 * @ORM\Table(name="active_events")
 */
class ActiveEvent
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="string", nullable=true) */
    private $image;

    /** @ORM\Column(type="string") */
    private $title;

    /** @ORM\Column(type="text", nullable=true) */
    private $description;

    /** @ORM\Column(type="datetime") */
    private $date;

    /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
    private $price;

    /** @ORM\Column(type="string", nullable=true) */
    private $link;

    /** @ORM\Column(type="integer", nullable=true) */
    private $externalId;

    /** @ORM\Column(type="string", nullable=true) */
    private $source;

    /** @ORM\Column(type="string", nullable=true) */
    private $typeEvent;

    /** @ORM\Column(type="string", nullable=true) */
    private $category;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getTypeEvent(): ?string
    {
        return $this->typeEvent;
    }

    public function setTypeEvent(?string $typeEvent): self
    {
        $this->typeEvent = $typeEvent;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }
}
