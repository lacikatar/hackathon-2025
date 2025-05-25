<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\AlertGenerator;
use App\Domain\Service\MonthlySummaryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly UserRepositoryInterface $users,
        private readonly ExpenseRepositoryInterface $expenses,
        private readonly AlertGenerator $alertGenerator,
        private readonly MonthlySummaryService $monthlySummary,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $this->users->find((int)$_SESSION['user_id']);

        //get data
        $queryParams = $request->getQueryParams();
        $selectedYear = (int)($queryParams['year'] ?? date('Y')); //extract year/month
        $selectedMonth = (int)($queryParams['month'] ?? date('m'));

        //get the categories from the env
        $categoriesConfig = $_ENV['EXPENSE_CATEGORIES'] ?? '["Groceries", "Utilities", "Transport", "Entertainment", "Housing", "Healthcare", "Other"]';
        $categories = json_decode($categoriesConfig, true) ?: [];

        //totals/cat
        $criteria = [
            'user_id' => $user->id,
            'date_from' => sprintf('%04d-%02d-01', $selectedYear, $selectedMonth),
            'date_to' => sprintf('%04d-%02d-01', $selectedYear, $selectedMonth + 1),
        ];

        //totals and avgs /cat
        $totals = $this->expenses->sumAmountsByCategory($criteria);
        $averages = $this->expenses->averageAmountsByCategory($criteria);
        $totalForMonth = $this->expenses->sumAmounts($criteria);

        //display format
        $categoryTotals = [];
        foreach ($totals as $total) {
            $category = $total['category'];
            $amount = $total['total'];
            $percentage = $totalForMonth > 0 ? ($amount / $totalForMonth) * 100 : 0;
            $categoryTotals[$category] = [
                'value' => $amount,
                'percentage' => $percentage
            ];
        }

        $categoryAvgs = [];
        foreach ($averages as $avg) {
            $category = $avg['category'];
            $amount = $avg['average'];
            $categoryAvgs[$category] = $amount;
        }

        //find years there were expenditures
        $years = $this->expenses->listExpenditureYears($user);

        //alert generation
        $alerts = $this->alertGenerator->generate($user, $selectedYear, $selectedMonth);

        return $this->render($response, 'dashboard.twig', [
            'alerts' => $alerts,
            'totalForMonth' => $totalForMonth,
            'totalsForCategories' => $categoryTotals,
            'averagesForCategories' => $categoryAvgs,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
