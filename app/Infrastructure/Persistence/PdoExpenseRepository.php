<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        //if not in db then insert
        if ($expense->id === null) {
            $query = 'INSERT into expenses (user_id, date, category, amount_cents, description) values (:user_id, :date, :category, :amount_cents, :description)';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([ 
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description
            ]);
            
            $expense->id = (int)$this->pdo->lastInsertId();
            //if already in db update
        } else {
            $query = 'UPDATE expenses SET date = :date, category = :category, amount_cents = :amount_cents, description = :description WHERE id = :id AND user_id = :user_id';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                'id' => $expense->id,
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description
            ]);
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        $query = 'SELECT * FROM expenses WHERE user_id = :user_id';     //starting query, the criteria is only getting added to the query if its passed into it
        $params = [':user_id' => $criteria['user_id']];                 //parameters of the query

        if (!empty($criteria['category'])) {
            $query .= ' AND category = :category';
            $params[':category'] = $criteria['category'];
        }

        if (!empty($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params[':date_from'] = $criteria['date_from'];
        }

        if (!empty($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params[':date_to'] = $criteria['date_to'];
        }

        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :from';
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':from', $from, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $expenses = [];
        foreach ($results as $result) {
            $expenses[] = $this->createExpenseFromData($result);
        }
        return $expenses;
    }

    public function countBy(array $criteria): int
    {
        
        
         $query= 'SELECT COUNT(*) FROM expenses where user_id=:user_id';
        $params=[':user_id'=> $criteria['user_id']];

        if(!empty($criteria['category'])){
            $query .=' AND category = :category';
            $params[':category']=$criteria['category'];
        }

        if(!empty($criteria['date_from']))
        {
            $query .= ' AND date >= :date_from';
            $params[':date_from']=$criteria['date_from'];
        }

        if(!empty($criteria['date_to']))
        {
            $query .= ' AND date <= :date_to';
            $params[':date_to']=$criteria['date_to'];
        }

       
        $stmt = $this->pdo->prepare($query);
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $result=(int)$stmt->fetchColumn();

    
       
        return $result;
    }

    public function listExpenditureYears(User $user): array
    {
        

        $query = 'SELECT DISTINCT strftime("%Y", date) as year FROM expenses';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $years;
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        $query = 'SELECT category, SUM(amount_cents) as total FROM expenses WHERE user_id = :user_id';
        $params = [':user_id' => $criteria['user_id']];
        
        if (!empty($criteria['category'])) {
            $query .= ' AND category = :category';
            $params[':category'] = $criteria['category'];
        }
        
        if (!empty($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params[':date_from'] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params[':date_to'] = $criteria['date_to'];
        }
        
        $query .= ' GROUP BY category';
        
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$result) {
            $result['total'] = $result['total'] / 100.0; 
        }
        return $results;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        $query = 'SELECT category, AVG(amount_cents) as average FROM expenses WHERE user_id = :user_id';
        $params = [':user_id' => $criteria['user_id']];
        
        if (!empty($criteria['category'])) {
            $query .= ' AND category = :category';
            $params[':category'] = $criteria['category'];
        }
        
        if (!empty($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params[':date_from'] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params[':date_to'] = $criteria['date_to'];
        }
        
        $query .= ' GROUP BY category';
        
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$result) {
            $result['average'] = $result['average'] / 100.0; 
        }
        return $results;
    }

    public function sumAmounts(array $criteria): float
    {
        $query = 'SELECT SUM(amount_cents) as total FROM expenses WHERE user_id = :user_id';
        $params = [':user_id' => $criteria['user_id']];
        
        if (!empty($criteria['category'])) {
            $query .= ' AND category = :category';
            $params[':category'] = $criteria['category'];
        }
        
        if (!empty($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params[':date_from'] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params[':date_to'] = $criteria['date_to'];
        }
        
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetchColumn();
        return $result !== null ? (float)$result / 100.0 : 0.0; 
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
