<?php

namespace App\Command;

use App\Repository\MOTWRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:motw-set-visual',
    description: 'Définit l\'URL de l\'image pour un MOTW',
)]
class SetMotwVisualCommand extends Command
{
    public function __construct(
        private MOTWRepository $motwRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID du MOTW')
            ->addArgument('url', InputArgument::REQUIRED, 'URL de l\'image')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');
        $url = $input->getArgument('url');

        $motw = $this->motwRepository->find($id);

        if (!$motw) {
            $io->error("MOTW avec l'ID $id introuvable.");
            return Command::FAILURE;
        }

        $motw->setVisual($url);
        $this->entityManager->flush();

        $io->success("L'image a été définie pour le MOTW '{$motw->getName()}'.");
        $io->info("URL: $url");

        return Command::SUCCESS;
    }
}
