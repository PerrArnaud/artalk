<?php

namespace App\Command;

use App\Repository\MOTWRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-motw',
    description: 'Affiche les informations de debug pour les MOTW',
)]
class DebugMotwCommand extends Command
{
    public function __construct(
        private MOTWRepository $motwRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $motws = $this->motwRepository->findAll();

        if (empty($motws)) {
            $io->warning('Aucun MOTW trouvé dans la base de données.');
            return Command::SUCCESS;
        }

        $io->title('Liste des MOTW');

        foreach ($motws as $motw) {
            $io->section("MOTW #{$motw->getId()}");
            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['ID', $motw->getId()],
                    ['Name', $motw->getName()],
                    ['Artist', $motw->getArtist()],
                    ['Date', $motw->getDate()?->format('Y-m-d')],
                    ['DatePost', $motw->getDatePost()?->format('Y-m-d')],
                    ['Visual', $motw->getVisual() ?: '(vide)'],
                    ['Slug', $motw->getSlug()],
                    ['Commentaires', count($motw->getReply())],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
