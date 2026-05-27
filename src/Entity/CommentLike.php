<?php

namespace App\Entity;

use App\Repository\CommentLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_comment_like', columns: ['user_id', 'comment_id'])]
class CommentLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Comment $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
