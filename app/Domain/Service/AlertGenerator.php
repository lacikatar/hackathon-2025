<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class AlertGenerator
{
    private array $categoryBudgets;

    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {
        $budgetsConfig = $_ENV['EXPENSE_BUDGETS'] ?? '{"Groceries": 300.00, "Utilities": 200.00, "Transport": 500.00, "Entertainment": 150.00, "Other": 100.00}';
        $this->categoryBudgets = json_decode($budgetsConfig, true) ?: [];
    }

    public function generate(User $user, int $year, int $month): array
    {
        $alerts = [];
        $criteria = [
            'user_id' => $user->id,
            'date_from' => sprintf('%04d-%02d-01', $year, $month),
            'date_to' => sprintf('%04d-%02d-01', $year, $month + 1),
        ];

        $totals = $this->expenses->sumAmountsByCategory($criteria);
        
        foreach ($totals as $total) {
            $category = $total['category'];
            $amount = $total['total']; 
            $budget = $this->categoryBudgets[$category] ?? 0.0;
            
            if ($budget > 0 && $amount > $budget) {
                $alerts[] = [
                    'category' => $category,
                    'amount' => $amount,
                    'budget' => $budget,
                    'exceeded_by' => $amount - $budget,
                    'message' => sprintf('You have exceeded your budget for %s by %.2f€', 
                        $category, 
                        $amount - $budget,
                        $budget,
                        $amount
                    )
                ];
            }
        }

        return $alerts;
    }
}
