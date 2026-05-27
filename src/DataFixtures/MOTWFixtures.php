<?php

namespace App\DataFixtures;

use App\Entity\ArtType;
use App\Entity\MOTW;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MOTWFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ArtTypeFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $artTypeRepo = $manager->getRepository(ArtType::class);

        $artTypes = [];
        foreach (['Peinture', 'Sculpture', 'Musique', 'Photographie', 'Littérature', 'Cinéma', 'Architecture', 'Danse'] as $name) {
            $found = $artTypeRepo->findOneBy(['name' => $name]);
            if ($found) {
                $artTypes[$name] = $found;
            }
        }

        $artworks = [
            // Peinture
            ['La Joconde', 'Léonard de Vinci', '1503-01-01', 'Peinture'],
            ['La Nuit étoilée', 'Vincent van Gogh', '1889-06-01', 'Peinture'],
            ['Le Cri', 'Edvard Munch', '1893-01-01', 'Peinture'],
            ['Les Demoiselles d\'Avignon', 'Pablo Picasso', '1907-06-01', 'Peinture'],
            ['La Persistance de la mémoire', 'Salvador Dalí', '1931-01-01', 'Peinture'],
            ['La Jeune Fille à la perle', 'Johannes Vermeer', '1665-01-01', 'Peinture'],
            // Musique
            ['Symphonie n°9', 'Ludwig van Beethoven', '1824-05-07', 'Musique'],
            ['Bohemian Rhapsody', 'Queen', '1975-10-31', 'Musique'],
            ['Clair de lune', 'Claude Debussy', '1905-01-01', 'Musique'],
            ['Kind of Blue', 'Miles Davis', '1959-08-17', 'Musique'],
            ['What\'s Going On', 'Marvin Gaye', '1971-05-21', 'Musique'],
            // Photographie
            ['Migrant Mother', 'Dorothea Lange', '1936-03-01', 'Photographie'],
            ['Earthrise', 'William Anders', '1968-12-24', 'Photographie'],
            ['V-J Day in Times Square', 'Alfred Eisenstaedt', '1945-08-14', 'Photographie'],
            // Littérature
            ['Don Quichotte', 'Miguel de Cervantes', '1605-01-16', 'Littérature'],
            ['À la recherche du temps perdu', 'Marcel Proust', '1913-11-14', 'Littérature'],
            ['Crime et Châtiment', 'Fiodor Dostoïevski', '1866-01-01', 'Littérature'],
            ['Cent ans de solitude', 'Gabriel García Márquez', '1967-05-30', 'Littérature'],
            // Cinéma
            ['Citizen Kane', 'Orson Welles', '1941-05-01', 'Cinéma'],
            ['2001, l\'Odyssée de l\'espace', 'Stanley Kubrick', '1968-04-02', 'Cinéma'],
            ['Metropolis', 'Fritz Lang', '1927-01-10', 'Cinéma'],
            ['La Règle du jeu', 'Jean Renoir', '1939-07-08', 'Cinéma'],
            // Sculpture
            ['Le Penseur', 'Auguste Rodin', '1904-01-01', 'Sculpture'],
            ['David', 'Michel-Ange', '1504-01-01', 'Sculpture'],
            // Architecture
            ['La Sagrada Família', 'Antoni Gaudí', '1882-03-19', 'Architecture'],
        ];

        // Spread DatePost over the last 25 weeks
        $baseDate = new \DateTime('now');

        foreach ($artworks as $i => $data) {
            [$name, $artist, $artworkDate, $artTypeName] = $data;

            $motw = new MOTW();
            $motw->setName($name);
            $motw->setArtist($artist);
            $motw->setDate(new \DateTime($artworkDate));
            $motw->setDatePost((clone $baseDate)->modify('-' . $i . ' weeks'));
            $motw->setArtType($artTypes[$artTypeName] ?? null);

            $manager->persist($motw);
        }

        $manager->flush();
    }
}
