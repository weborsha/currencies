<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Form\CurrencyType;
use App\Service\CurrencyRateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

class DashboardController extends AbstractDashboardController
{
    private $entityManager;
    private $currencyRateService;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, CurrencyRateService $currencyRateService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->currencyRateService = $currencyRateService;
        $this->logger = $logger;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $exchangeRates = $this->entityManager->getRepository(ExchangeRate::class)->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'exchangeRates' => $exchangeRates,
        ]);
    }

    #[Route('/admin/update-currency-rates', name: 'update_currency_rates')]
    public function updateCurrencyRates(): RedirectResponse
    {
        try {
            $this->currencyRateService->updateCurrencyRates();
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin');
    }

    #[Route('/admin/currency-rates', name: 'currency_rates')]
    public function getCurrencyRates(): JsonResponse
    {
        $exchangeRates = $this->entityManager->getRepository(ExchangeRate::class)->findAll();
        $data = [];

        foreach ($exchangeRates as $exchangeRate) {
            $data[] = [
                'currency' => $exchangeRate->getCurrency()->getCode(),
                'rate' => $exchangeRate->getRate(),
                'updated_at' => $exchangeRate->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/admin/add-currency-ajax', name: 'add_currency_ajax', methods: ['POST'])]
    #[IsCsrfTokenValid('add_currency', '_token')]
    public function addCurrencyAjax(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->logger->info('Received data', ['data' => $data]);

        $currency = new Currency();
        $form = $this->createForm(CurrencyType::class, $currency);

        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($currency);
                $this->entityManager->flush();
                return new JsonResponse(['status' => 'success', 'message' => 'Валюта успешно добавлена, теперь можете обновить курсы валют']);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => 'error', 'message' => 'Ошибка при добавлении валюты', 'errors' => [$e->getMessage()]]);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $this->logger->error('Form error', ['error' => $error->getMessage()]);
            $errors[] = $error->getMessage();
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Ошибка при добавлении валюты', 'errors' => $errors]);
    }

    #[Route('/admin/delete-currency/{id}', name: 'delete_currency')]
    public function deleteCurrency(int $id): RedirectResponse
    {
        $currency = $this->entityManager->getRepository(Currency::class)->find($id);

        if ($currency) {
            $this->entityManager->remove($currency);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Панель управления');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Курсы', 'fa fa-home');
        yield MenuItem::linkToRoute('Обновить курсы', 'fa fa-sync', 'update_currency_rates');
        yield MenuItem::linkToRoute('Курсы валют (json)', 'fa fa-money', 'currency_rates');
    }
}
