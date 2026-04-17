<?php

namespace App\Command;

use App\Service\CheckoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-virements',
    description: 'Annule les commandes par virement en attente depuis plus de 14 jours et libère les unités.',
)]
final class CleanVirementsCommand extends Command
{
    public function __construct(private readonly CheckoutService $checkoutService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->checkoutService->cleanExpiredVirements();

        $io->success('Nettoyage des virements expirés effectué.');

        return Command::SUCCESS;
    }
}
