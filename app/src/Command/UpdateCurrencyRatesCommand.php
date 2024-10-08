<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Service\CurrencyRateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCurrencyRatesCommand extends Command
{
    protected static $defaultName = 'app:update-currency-rates';

    private $currencyRateService;
    private $entityManager;

    public function __construct(CurrencyRateService $currencyRateService, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->currencyRateService = $currencyRateService;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Обновление курсов валют с ExchangeRate-API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $currencies = $this->entityManager->getRepository(Currency::class)->findAll();

        foreach ($currencies as $currency) {
            $rate = $this->currencyRateService->getCurrencyRate($currency->getCode());
            if ($rate !== null) {
                $exchangeRate = new ExchangeRate();
                $exchangeRate->setCurrency($currency);
                $exchangeRate->setRate($rate);
                $exchangeRate->setUpdatedAt(new \DateTime());
                $this->entityManager->persist($exchangeRate);
            }
        }

        $this->entityManager->flush();

        $io->success('Курсы валют обновлены.');

        return Command::SUCCESS;
    }
}
