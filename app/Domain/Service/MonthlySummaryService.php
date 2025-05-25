<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        $criteria = [
            'user_id' => $user->id,
            'date_from' => sprintf('%04d-%02d-01', $year, $month),
            'date_to' => sprintf('%04d-%02d-01', $year, $month),
        ];
        
        return $this->expenses->sumAmounts($criteria);
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        $criteria = [
            'user_id' => $user->id,
            'date_from' => sprintf('%04d-%02d-01', $year, $month),
            'date_to' => sprintf('%04d-%02d-01', $year, $month),
        ];
        
        $totals = $this->expenses->sumAmountsByCategory($criteria);
        $result = [];
        
        foreach ($totals as $total) {
            $result[$total['category']] = $total['total'];
        }
        
        return $result;
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        $criteria = [
            'user_id' => $user->id,
            'date_from' => sprintf('%04d-%02d-01', $year, $month),
            'date_to' => sprintf('%04d-%02d-01', $year, $month),
        ];
        
        $averages = $this->expenses->averageAmountsByCategory($criteria);
        $result = [];
        
        foreach ($averages as $avg) {
            $result[$avg['category']] = $avg['average'];
        }
        
        return $result;
    }
}
