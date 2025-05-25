<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
       
        $criteria = [ 'user_id' =>$user->id,
                        'date_from' => sprintf('%04d-%02d-01', $year, $month),
                        'date_to' => sprintf('%04d-%02d-01', $year, $month),

    ];

        return $this->expenses->findBy($criteria, ($pageNumber - 1) * $pageSize, $pageSize);
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0.');
        }

        if (empty($description)) {
            throw new Exception('Description is required.');
        }

        if (empty($category)) {
            throw new Exception('Category is required.');
        }

        $expense = new Expense(
            null,
            $user->id,
            $date,
            $category,
            0, 
            $description
        );
        $expense->setAmount($amount);

        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0.');
        }

        if (empty($description)) {
            throw new Exception('Description is required.');
        }

        if (empty($category)) {
            throw new Exception('Category is required.');
        }

        $updatedExpense = new Expense(
            $expense->id,
            $expense->userId,
            $date,
            $category,
            0, 
            $description
        );
        $updatedExpense->setAmount($amount);

        $this->expenses->save($updatedExpense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails
        //I couldnt do this

        return 0; // number of imported rows
    }
}
