<?php

namespace App\EventListener;

use App\Entity\MOTW;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsEntityListener(event: Events::prePersist, entity: MOTW::class)]
#[AsEntityListener(event: Events::preUpdate, entity: MOTW::class)]
class MOTWSlugListener
{
    public function __construct(
        private SluggerInterface $slugger
    ) {
    }

    public function prePersist(MOTW $motw): void
    {
        $this->generateSlug($motw);
    }

    public function preUpdate(MOTW $motw): void
    {
        $this->generateSlug($motw);
    }

    private function generateSlug(MOTW $motw): void
    {
        if (!$motw->getSlug() || $motw->getSlug() === '') {
            $slug = $this->slugger->slug($motw->getName() . '-' . $motw->getArtist())->lower();
            $motw->setSlug($slug);
        }
    }
}
