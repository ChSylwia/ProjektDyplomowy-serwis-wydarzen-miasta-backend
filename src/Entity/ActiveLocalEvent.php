<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ActiveLocalEventRepository;

/**
 * @ORM\Entity(repositoryClass=ActiveLocalEventRepository::class)
 * @ORM\Table(name="active_local_events")
 */
class ActiveLocalEvent
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

    /** @ORM\Column(name="price_min", type="decimal", precision=10, scale=2, nullable=true) */
    private $priceMin;

    /** @ORM\Column(type="string", nullable=true) */
    private $link;

    /** @ORM\Column(type="integer", nullable=true) */
    private $userId;

    /** @ORM\Column(type="string", nullable=true) */
    private $typeEvent;

    /** @ORM\Column(type="boolean", nullable=true) */
    private $deleted;

    /** @ORM\Column(type="string", nullable=true) */
    private $category;

    /** @ORM\Column(name="price_max", type="decimal", precision=10, scale=2, nullable=true) */
    private $priceMax;

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

    public function getPriceMin(): ?string
    {
        return $this->priceMin;
    }

    public function setPriceMin(?string $priceMin): self
    {
        $this->priceMin = $priceMin;
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
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

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted;
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

    public function getPriceMax(): ?string
    {
        return $this->priceMax;
    }

    public function setPriceMax(?string $priceMax): self
    {
        $this->priceMax = $priceMax;
        return $this;
    }
}
