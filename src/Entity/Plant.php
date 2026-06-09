<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Controller\PlantsController;
use App\Repository\PlantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Filter\PlantSearchFilter;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: PlantRepository::class)]
#[ApiResource(
    paginationEnabled: true,
    paginationItemsPerPage: 10, // Optionnel : pour tester la pagination plus facilement
    order: ['id' => 'DESC'],
    operations: [
        new Get(normalizationContext: ['groups' => ['plant:read']]),
        new GetCollection(normalizationContext: ['groups' => ['plant:read']]),
        new Patch(denormalizationContext: ['groups' => ['plant:write']]),
        new Post(denormalizationContext: ['groups' => ['plant:write']]),
        new Put(denormalizationContext: ['groups' => ['plant:write']]),
        new Delete()
    ]
)]
#[ApiFilter(PlantSearchFilter::class)]
class Plant
{

    #[Groups(['plant:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['plant:read'])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Groups(['plant:read', 'plant:write'])]
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'plant', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])] // <- C'est magique, Doctrine trie tout seul à la récupération
    private Collection $images;

    #[Groups(['plant:read'])]
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;


    // Getters et Setters indispensables

    /**
     * @var Collection<int, Category>
     */
    #[Groups(['plant:read'])]
     #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'plants')]
    private Collection $categories;

    #[Groups(['plant:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commonName = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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


    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCommonName(): ?string
    {
        return $this->commonName;
    }

    public function setCommonName(?string $commonName): static
    {
        $this->commonName = $commonName;

        return $this;
    }

/**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setPlant($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // Set the owning side to null (unless already changed)
            if ($image->getPlant() === $this) {
                $image->setPlant(null);
            }
        }

        return $this;
    }

}
