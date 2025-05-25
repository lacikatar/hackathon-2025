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
        // TODO: Implement save() method.
        $query = 'INSERT into expenses (user_id, date, category, amount_cents, description) values (:user_id, :date, :category, :amount_cents, :description))';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([ 'user_id' => $expense->user_id,
          'date' => $expense ->date,
          'category' => $expense->category,
          'amount_cents' => $expense->amountCents,
          'description' => $expense->description

        ]);
        
        $expense->id=(int)$this->pdo->lastInsertId();

    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        $query= 'SELECT * FROM expenses where user_id=:user_id';
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

        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :from';
        $stmt = $this->pdo->prepare($query);
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':from',$from,PDO::PARAM_INT);
        $stmt->execute();

        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);  

    
        return $result;
    }


    public function countBy(array $criteria): int
    {
        //for pagination ?
        // TODO: Implement countBy() method.
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
        // TODO: Implement listExpenditureYears() method.

        $query = 'SELECT Distinct Year(e.Date) From expenditures e';
        $stmt = $this -> pdo-> prepare($query);
        $stmt->execute();
        $years= $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $years;
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        $query = ' SELECT category, SUM(Amount_cents) from expenses where user_id=:user_id';
        $params=[':user_id'=> $criteria['user_id']];
        if(!empty($criteria['category'])){
            $query .= ' AND category = :category ';
            $params[':category']=$criteria['category'];
        }
        if(!empty($criteria['date_from'])){
            $query .= ' AND date >= :date_from';
            $params[':date_from']=$criteria['date_from'];

        }
        if(!empty($criteria['date_to'])){
            $query .= ' AND date <= :date_to';
            $params[':date_to']=$criteria['date_to'];
        }

        $query .= ' GROUP BY category';

        $stmt= $this->pdo->prepare($query);
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
       
        // TODO: Implement averageAmountsByCategory() method.
        $query = ' SELECT category, AVG(Amount_cents) from expenses where user_id=:user_id';
        $params=[':user_id'=> $criteria['user_id']];
        if(!empty($criteria['category'])){
            $query .= ' AND category = :category ';
            $params[':category']=$criteria['category'];
        }
        if(!empty($criteria['date_from'])){
            $query .= ' AND date >= :date_from';
            $params[':date_from']=$criteria['date_from'];

        }
        if(!empty($criteria['date_to'])){
            $query .= ' AND date <= :date_to';
            $params[':date_to']=$criteria['date_to'];
        }

        $query .= ' GROUP BY category';

        $stmt= $this->pdo->prepare($query);
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.

        $query = ' SELECT  SUM(Amount_cents) from expenses where user_id=:user_id';
        $params=[':user_id'=> $criteria['user_id']];
        if(!empty($criteria['category'])){
            $query .= ' AND category = :category ';
            $params[':category']=$criteria['category'];
        }
        if(!empty($criteria['date_from'])){
            $query .= ' AND date >= :date_from';
            $params[':date_from']=$criteria['date_from'];

        }
        if(!empty($criteria['date_to'])){
            $query .= ' AND date <= :date_to';
            $params[':date_to']=$criteria['date_to'];
        }

       

        $stmt= $this->pdo->prepare($query);
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result=$stmt->fetchColumn();
        return $result !==null ? (float) $result :0.0;

        
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
