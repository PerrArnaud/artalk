<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $validated = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'Reply')]
    private ?self $comment = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'comment')]
    private Collection $Reply;

    #[ORM\ManyToOne(inversedBy: 'Reply')]
    private ?MOTW $mOTW = null;

    #[ORM\ManyToOne(inversedBy: 'Comments')]
    private ?User $user = null;

    public function __construct()
    {
        $this->Reply = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?self
    {
        return $this->comment;
    }

    public function setComment(?self $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getReply(): Collection
    {
        return $this->Reply;
    }

    public function addReply(self $reply): static
    {
        if (!$this->Reply->contains($reply)) {
            $this->Reply->add($reply);
            $reply->setComment($this);
        }

        return $this;
    }

    public function removeReply(self $reply): static
    {
        if ($this->Reply->removeElement($reply)) {
            // set the owning side to null (unless already changed)
            if ($reply->getComment() === $this) {
                $reply->setComment(null);
            }
        }

        return $this;
    }

    public function getMOTW(): ?MOTW
    {
        return $this->mOTW;
    }

    public function setMOTW(?MOTW $mOTW): static
    {
        $this->mOTW = $mOTW;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        // Normalise les retours à la ligne Windows/Mac/Linux
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // limite à maximum 2 retours à la ligne consécutifs
        $content = preg_replace("/\n{3,}/", "\n\n", $content);

        // supprime les lignes vides en début et fin
        $content = trim($content);

        $this->content = $content;

        return $this;
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): static
    {
        $this->validated = $validated;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
