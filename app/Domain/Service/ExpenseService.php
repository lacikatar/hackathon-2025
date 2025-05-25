<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses
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
        // TODO: implement this to create a new expense entity, perform validation, and persist

          if ($amount <= 0) {
        throw new InvalidArgumentException('Amount must be positive.');
    }

    if (empty(trim($category))) {
        throw new InvalidArgumentException('Category cannot be empty.');
    }

    if (empty(trim($description))) {
        throw new InvalidArgumentException('Description cannot be empty.');
    }
        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->id, $date, $category, (int)$amount, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
        if ($amount <= 0) {
        throw new InvalidArgumentException('Amount must be positive.');
    }

    if (empty(trim($category))) {
        throw new InvalidArgumentException('Category cannot be empty.');
    }
     if (empty(trim($description))) {
        throw new InvalidArgumentException('Description cannot be empty.');

    }

    $expense->setAmountCents($amountCents);
    $expense->setDescription($description);
    $expense->setDate($date);
    $expense->setCategory($category);

    // Persist the updated expense
    $this->expenses->save($expense);
}


    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
