<?php

namespace App\Entity;

use App\Repository\MOTWRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MOTWRepository::class)]
class MOTW
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['motw:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['motw:read'])]
    private ?string $Name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['motw:read'])]
    private ?\DateTime $Date = null;

    #[ORM\Column(length: 255)]
    #[Groups(['motw:read'])]
    private ?string $Artist = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'mOTW')]
    private Collection $Reply;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['motw:read'])]
    private ?\DateTime $DatePost = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['motw:read'])]
    private ?string $visual = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['motw:read', 'comment:read'])]
    private ?string $slug = null;

    public function __construct()
    {
        $this->Reply = new ArrayCollection();
    }

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
        return $this->visual;
    }

    public function setVisual(?string $visual): static
    {
        $this->visual = $visual;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getReply(): Collection
    {
        return $this->Reply;
    }

    public function addReply(Comment $reply): static
    {
        if (!$this->Reply->contains($reply)) {
            $this->Reply->add($reply);
            $reply->setMOTW($this);
        }

        return $this;
    }

    public function removeReply(Comment $reply): static
    {
        if ($this->Reply->removeElement($reply)) {
            // set the owning side to null (unless already changed)
            if ($reply->getMOTW() === $this) {
                $reply->setMOTW(null);
            }
        }

        return $this;
    }

    public function getDatePost(): ?\DateTime
    {
        return $this->DatePost;
    }

    public function setDatePost(\DateTime $DatePost): static
    {
        $this->DatePost = $DatePost;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    #[Groups(['motw:read'])]
    public function getCommentCount(): int
    {
        return $this->Reply->count();
    }

    #[Groups(['motw:read'])]
    public function getFullVisualUrl(): ?string
    {
        if (!$this->visual) {
            return null;
        }
        // Return relative path - the base URL will be added by the mobile app
        return '/uploads/images/' . $this->visual;
    }
}
