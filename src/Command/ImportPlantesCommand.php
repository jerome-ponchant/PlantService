<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Plant;
use Directory;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:import-plantes',
    description: 'Add a short description for your command',
)]
class ImportPlantesCommand extends Command
{
    protected EntityManager $manager;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

            $finder = new Finder();
            // Remplace par ton chemin Windows (attention aux antislashes doubles)
            $finder->files()->in("C:/Documents/La Cadène/Reconnaissance plantes/Planches sans noms")->name(['*.doc', '*.docx']);

            if (!$finder->hasResults()) {
                $output->writeln('Aucun fichier trouvé.');
                return Command::FAILURE;
            }

            foreach ($finder as $file) {
                $nomNettoye = $file->getBasename('.' . $file->getExtension());
                $nomImage = $nomNettoye . '.png';

                // 1. Création de l'entité
                $plante = new Plant();
                $plante->setName($nomNettoye);

                // On définit l'URL relative (accessible via le serveur web)
                $plante->setImageUrl('/images/plantes/' . $nomImage);

                $this->entityManager->persist($plante);

                // 2. Lancer la conversion LibreOffice
                // On définit le chemin physique où enregistrer le fichier
                $publicDir = 'C:/Angular/PlantService/public/images/plantes';

                $process = new Process([
                    'C:\Program Files\LibreOffice\program\soffice',
                    '--headless',
                    '--convert-to', 'png',
                    '--outdir', $publicDir,
                    $file->getRealPath()
                ]);
                $process->run();

                $output->writeln("Importé et converti : " . $nomNettoye);
            }

            $this->entityManager->flush();

            return Command::SUCCESS;
        }

    }

