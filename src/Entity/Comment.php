<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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
}
