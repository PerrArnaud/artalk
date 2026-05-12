<?php

namespace App\DataFixtures;

use App\Entity\ArtType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArtTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = [
            'Peinture',
            'Sculpture',
            'Musique',
            'Photographie',
            'Littérature',
            'Cinéma',
            'Architecture',
            'Danse',
        ];

        foreach ($types as $name) {
            $artType = new ArtType();
            $artType->setName($name);
            $manager->persist($artType);
        }

        $manager->flush();
    }
}
