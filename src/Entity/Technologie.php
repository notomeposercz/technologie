<?php
/**
 * Doctrine Entity pro technologie potisku
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ps_technologie")
 * @ORM\Entity(repositoryClass="PrestaShop\Module\Technologie\Repository\TechnologieRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Technologie
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id_technologie", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private ?string $image = null;

    /**
     * @ORM\Column(name="position", type="integer", nullable=false, options={"default": 0})
     */
    private int $position = 0;

    /**
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default": true})
     */
    private bool $active = true;

    /**
     * @ORM\Column(name="date_add", type="datetime", nullable=false)
     */
    private \DateTime $dateAdd;

    /**
     * @ORM\Column(name="date_upd", type="datetime", nullable=false)
     */
    private \DateTime $dateUpd;

    public function __construct()
    {
        $this->dateAdd = new \DateTime();
        $this->dateUpd = new \DateTime();
        // Inicializace výchozích hodnot pro povinné properties
        $this->name = '';
        $this->position = 0;
        $this->active = true;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getDateAdd(): \DateTime
    {
        return $this->dateAdd;
    }

    public function getDateUpd(): \DateTime
    {
        return $this->dateUpd;
    }

    // Setters
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function updateDateUpd(): void
    {
        $this->dateUpd = new \DateTime();
    }

    /**
     * Získání cesty k obrázku pro zobrazení
     */
    public function getImagePath(): ?string
    {
        if (!$this->image) {
            return null;
        }
        
        return _MODULE_DIR_ . 'technologie/uploads/' . $this->image;
    }

    /**
     * Získání absolutní cesty k obrázku
     */
    public function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }
        
        return __PS_BASE_URI__ . 'modules/technologie/uploads/' . $this->image;
    }
}
