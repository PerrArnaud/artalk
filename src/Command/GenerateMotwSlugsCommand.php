<?php

namespace App\Command;

use App\Repository\MOTWRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:generate-motw-slugs',
    description: 'Génère les slugs pour les MOTW existants qui n\'en ont pas',
)]
class GenerateMotwSlugsCommand extends Command
{
    public function __construct(
        private MOTWRepository $motwRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $motws = $this->motwRepository->findAll();
        $updated = 0;

        foreach ($motws as $motw) {
            if (!$motw->getSlug() || $motw->getSlug() === '') {
                $slug = $this->slugger->slug($motw->getName() . '-' . $motw->getArtist())->lower();
                $motw->setSlug($slug);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
            $io->success("$updated MOTW(s) ont été mis à jour avec des slugs.");
        } else {
            $io->info('Tous les MOTW ont déjà des slugs.');
        }

        return Command::SUCCESS;
    }
}
