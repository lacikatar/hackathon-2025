<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use App\Domain\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly UserRepositoryInterface $users,
        private readonly ExpenseService $expenseService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        $user_id=$_SESSION['user_id'];
        $user=$this->users->find($user_id);

          $queryParams = $request->getQueryParams();

        $year = (int)($queryParams['year'] ?? date('Y'));
        $month = (int)($queryParams['month'] ?? date('m'));
       

        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
       
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $expenses = $this->expenseService->list($user, $year, $month, $page, $pageSize);

        return $this->render($response, 'expenses/index.twig', [
           'expenses' => $expenses,
           'year' => $year,
            'month' => $month,
            'page' => $page,
            'pageSize' => $pageSize,
        
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
 $categories = $this->config['expense_categories'] ?? ['Food', 'Transport', 'Entertainment', 'Other'];
        return $this->render($response, 'expenses/create.twig', ['categories' => $categories,]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

       
    $data = $request->getParsedBody();

    $amount = (float)($data['amount'] ?? 0);
    $description = trim($data['description'] ?? '');
    $date = !empty($data['date']) ? new \DateTimeImmutable($data['date']) : null;
    $category = $data['category'] ?? '';

    $errors = [];

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
        // On error, re-render form with errors and previous input
        $categories = $this->config['expense_categories'] ?? [];
        return $this->render($response, 'expenses/create.twig', [
            'errors' => $errors,
            'categories' => $categories,
            'input' => $data,
        ]);
    }

    $user_id = $_SESSION['user_id'];
    $user = $this->users->find($user_id);

    $this->expenseService->create($user, $amount, $description, $date, $category);

    // On success, redirect to the expenses index page
    return $response
        ->withHeader('Location', '/expenses')
        ->withStatus(302);
}

    

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

          $userId = $_SESSION['user_id'];
    $user = $this->users->find($userId);

    // 2. Get the expense ID from route params
    $expenseId = (int)($routeParams['id'] ?? 0);

    // 3. Fetch the expense by ID
    $expense = $this->expenses->find($expenseId);

    // 4. Check if expense exists and belongs to user
    if (!$expense || $expense->userId !== $user->id) {
        // Forbidden (403)
        return $response->withStatus(403);
    }

    // 5. Get available categories (you may load from config)
    $categories = $this->config->get('categories', []); // adjust this if needed

    // 6. Render edit view
    return $this->render($response, 'expenses/edit.twig', [
        'expense' => $expense,
        'categories' => $categories,
    ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        // 1. Get logged-in user
    $userId = $_SESSION['user_id'];
    $user = $this->users->find($userId);

    // 2. Get the expense ID from route params
    $expenseId = (int)($routeParams['id'] ?? 0);
    $expense = $this->expenses->find($expenseId);

    // 3. Ensure the expense exists and belongs to the logged-in user
    if (!$expense || $expense->userId !== $user->id) {
        return $response->withStatus(403);
    }

    // 4. Get form data from request
    $data = $request->getParsedBody();
    $amount = (float)($data['amount'] ?? 0);
    $description = trim($data['description'] ?? '');
    $category = trim($data['category'] ?? '');
    $dateString = $data['date'] ?? '';
    
    // 5. Validate and convert date
    try {
        $date = new DateTimeImmutable($dateString);
    } catch (Exception $e) {
        $date = null;
    }

    $errors = [];

    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }

    if (empty($description)) {
        $errors[] = 'Description is required.';
    }

    if (empty($category)) {
        $errors[] = 'Category is required.';
    }

    if (!$date) {
        $errors[] = 'Invalid date format.';
    }

    if (!empty($errors)) {
        // Re-render form with errors
        $categories = $this->config->get('categories', []);
        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense,
            'categories' => $categories,
            'errors' => $errors,
            'input' => $data
        ]);
    }

    // 6. Update and save
    $this->expenseService->update($expense, $amount, $description, $date, $category);

    // 7. Redirect on success
    return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

          // Get the logged-in user's ID
    $userId = $_SESSION['user_id'];
    $user = $this->users->find($userId);

    // Get the expense ID from the route parameters
    $expenseId = (int)($routeParams['id'] ?? 0);

    // Load the expense to be deleted
    $expense = $this->expenses->find($expenseId);

    // Check if the expense exists and belongs to the logged-in user
    if (!$expense || $expense->userId !== $user->id) {
        return $response->withStatus(403);
    }

    // Call repository to delete the expense
    $this->expenses->delete($expense);

    // Redirect to the expenses index page
    return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
}
