<?php

namespace App\Service;

use GuzzleHttp\Client;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Currency;
use App\Entity\ExchangeRate;
use Psr\Log\LoggerInterface;

class CurrencyRateService
{
    private $client;
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->client = new Client([
            'base_uri' => 'https://open.er-api.com/v6/',
        ]);
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function getCurrencyRates(): array
    {
        $exchangeRateRepository = $this->entityManager->getRepository(ExchangeRate::class);
        $exchangeRates = $exchangeRateRepository->findAll();

        $rates = [];
        foreach ($exchangeRates as $exchangeRate) {
            $rates[] = [
                'currency' => $exchangeRate->getCurrency()->getCode(),
                'rate' => $exchangeRate->getRate(),
                'updated_at' => $exchangeRate->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $rates;
    }

    public function parseCurrencyRates(): array
    {
        try {
            $response = $this->client->request('GET', 'latest/usd');
            $data = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching currency rates: ' . $e->getMessage());
            return [];
        }

        if (!isset($data['rates'])) {
            return [];
        }

        $currencyRepository = $this->entityManager->getRepository(Currency::class);
        $currencies = $currencyRepository->findAll();

        $rates = [];
        foreach ($currencies as $currency) {
            $code = strtoupper($currency->getCode()); // Приводим код валюты к верхнему регистру
            if (isset($data['rates'][$code])) {
                $rates[$code] = $data['rates'][$code];
            }
        }

        return $rates;
    }


    public function updateCurrencyRates(): void
    {
        $rates = $this->parseCurrencyRates();
        foreach ($rates as $code => $rate) {
            $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => $code]);
            if ($currency) {
                $exchangeRate = $this->entityManager->getRepository(ExchangeRate::class)->findOneBy(['currency' => $currency]);
                if ($exchangeRate) {
                    // Обновляем существующую запись
                    $exchangeRate->setRate($rate);
                    $exchangeRate->setUpdatedAt(new \DateTime());
                    $this->logger->info("Обновлен курс для валюты: {$code}, rate: {$rate}");
                } else {
                    // Создаем новую запись
                    $exchangeRate = new ExchangeRate();
                    $exchangeRate->setCurrency($currency);
                    $exchangeRate->setRate($rate);
                    $exchangeRate->setUpdatedAt(new \DateTime());
                    $this->entityManager->persist($exchangeRate);
                    $this->logger->info("Создан новый курс для валюты: {$code}, rate: {$rate}");
                }
            } else {
                $this->logger->warning("Валюта не найдена: {$code}");
            }
        }
        $this->entityManager->flush();
    }

}
