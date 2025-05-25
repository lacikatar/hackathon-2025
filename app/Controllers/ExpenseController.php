<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly UserRepositoryInterface $users,
        private readonly ExpenseService $expenseService,
        private readonly ExpenseRepositoryInterface $expenses,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $this->users->find((int)$_SESSION['user_id']);
        
        $queryParams = $request->getQueryParams();
        $selectedYear = (int)($queryParams['year'] ?? date('Y'));
        $selectedMonth = (int)($queryParams['month'] ?? date('m'));
        $selectedCategory = $queryParams['category'] ?? null;

        // Get categories from environment
        $categoriesConfig = $_ENV['EXPENSE_CATEGORIES'] ?? '["Groceries", "Utilities", "Transport", "Entertainment", "Housing", "Healthcare", "Other"]';
        $categories = json_decode($categoriesConfig, true) ?: [];

        // Build criteria for filtering
        $criteria = [
            'user_id' => $user->id,
        ];

        if ($selectedCategory) {
            $criteria['category'] = $selectedCategory;
        }

        if ($selectedYear && $selectedMonth) {
            $criteria['date_from'] = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
            $criteria['date_to'] = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth + 1);
        }

        // Get expenses with pagination
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $expenses = $this->expenses->findBy($criteria, $offset, $perPage);
        $totalExpenses = $this->expenses->countBy($criteria);
        $totalPages = ceil($totalExpenses / $perPage);

        // Get available years for the year selector
        $years = $this->expenses->listExpenditureYears($user);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'categories' => $categories,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedCategory' => $selectedCategory,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // Get categories from environment
        $categoriesConfig = $_ENV['EXPENSE_CATEGORIES'] ?? '["Groceries", "Utilities", "Transport", "Entertainment", "Housing", "Healthcare", "Other"]';
        $categories = json_decode($categoriesConfig, true) ?: [];

        // Set default date to today
        $defaultDate = date('Y-m-d');

        return $this->render($response, 'expenses/create.twig', [
            'categories' => $categories,
            'defaultDate' => $defaultDate
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $amount = (float)($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');
        $date = !empty($data['date']) ? new DateTimeImmutable($data['date']) : null;
        $category = $data['category'] ?? '';

        $errors = [];

        //check the requirements
        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than 0.';
        }

        if (empty($description)) {
            $errors['description'] = 'Description is required.';
        }

        if (!$date) {
            $errors['date'] = 'Invalid date.';
        }

        if (empty($category)) {
            $errors['category'] = 'Category is required.';
        }

        if (!empty($errors)) {
            //if error -> re render
            $categoriesConfig = $_ENV['EXPENSE_CATEGORIES'] ?? '["Groceries", "Utilities", "Transport", "Entertainment", "Housing", "Healthcare", "Other"]';
            $categories = json_decode($categoriesConfig, true) ?: [];
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $errors,
                'categories' => $categories,
                'input' => $data,
                'defaultDate' => $data['date'] ?? date('Y-m-d')
            ]);
        }

        $user_id = $_SESSION['user_id'];
        $user = $this->users->find($user_id);

        $this->expenseService->create($user, $amount, $description, $date, $category);

        // if succes->expenses
        return $response
            ->withHeader('Location', '/expenses')
            ->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        $userId = $_SESSION['user_id'];
        $user = $this->users->find($userId);

        //getting the id of the expense
        $expenseId = (int)($routeParams['id'] ?? 0);

        
        $expense = $this->expenses->find($expenseId);

        //get cats from env
        $categoriesConfig = $_ENV['EXPENSE_CATEGORIES'] ?? '["Groceries", "Utilities", "Transport", "Entertainment", "Other"]';
        $categories = json_decode($categoriesConfig, true) ?: [];

        
        $expenseData = [
            'id' => $expense->id,
            'date' => $expense->date,
            'category' => $expense->category,
            'amount_cents' => $expense->amountCents,
            'description' => $expense->description
        ];

        
        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expenseData,
            'categories' => $categories,
        ]);
    }

    private function validateExpenseData(array $data): array
    {
        $errors = [];
        $amount = (float)($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');
        $date = !empty($data['date']) ? new DateTimeImmutable($data['date']) : null;
        $category = $data['category'] ?? '';

        if (empty($description)) {
            $errors['description'] = 'Description is required.';
        }

        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than 0.';
        }

        if (empty($date)) {
            $errors['date'] = 'Date is required.';
        }

        if (empty($category)) {
            $errors['category'] = 'Category is required.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'amount' => $amount,
                'description' => $description,
                'date' => $date,
                'category' => $category
            ]
        ];
    }

    private function processExpenseUpdate(int $expenseId, array $validatedData): void
    {
        $expense = $this->expenses->find($expenseId);

        $expense->setDate($validatedData['date']);
        $expense->setCategory($validatedData['category']);
        $expense->setAmount((int)($validatedData['amount'] * 100));
        $expense->setDescription($validatedData['description']);

        $this->expenseService->update(
            $expense,
            $validatedData['amount'],
            $validatedData['description'],
            $validatedData['date'],
            $validatedData['category']
        );
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $expenseId = (int)$args['id'];

        $validation = $this->validateExpenseData($data);
        if (!empty($validation['errors'])) {
            return $this->render($response, 'expenses/edit.twig', [
                'errors' => $validation['errors'],
                'expense' => $this->expenses->find($expenseId),
                'categories' => json_decode($_ENV['EXPENSE_CATEGORIES'] ?? '[]', true) ?: []
            ]);
        }

        try {
            $this->processExpenseUpdate($expenseId, $validation['data']);
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Expense updated successfully.'
            ];
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (Exception $e) {
            $this->logger->error('Failed to update expense: ' . $e->getMessage());
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to update expense. Please try again.'
            ];
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        }
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {

        $expenseId = (int)($routeParams['id']);
       
        //call delete
        $this->expenses->delete($expenseId);

        //redirect
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
}
