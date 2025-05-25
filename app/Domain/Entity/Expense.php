<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

final class Expense
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public DateTimeImmutable $date,
        public string $category,
        public int $amountCents,
        public string $description,
    ) {}

    public function getAmount(): float
    {
        return $this->amountCents / 100.0;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function setAmount(float $amount): void
    {
        $this->amountCents = (int)($amount * 100);
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
