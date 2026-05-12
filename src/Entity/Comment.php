<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['comment:read'])]
    private ?string $content = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['comment:read'])]
    private bool $validated = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $hidden = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['comment:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'Reply')]
    private ?self $comment = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'comment')]
    #[Groups(['comment:read'])]
    private Collection $Reply;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'comment', orphanRemoval: true)]
    private Collection $reports;

    #[ORM\ManyToOne(inversedBy: 'Reply')]
    #[Groups(['comment:read'])]
    private ?MOTW $mOTW = null;

    #[ORM\ManyToOne(inversedBy: 'Comments')]
    #[Groups(['comment:read'])]
    private ?User $user = null;

    public function __construct()
    {
        $this->Reply = new ArrayCollection();
        $this->reports = new ArrayCollection();
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

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setComment($this);
        }

        return $this;
    }

    public function removeReport(Report $report): static
    {
        if ($this->reports->removeElement($report)) {
            if ($report->getComment() === $this) {
                $report->setComment(null);
            }
        }

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
