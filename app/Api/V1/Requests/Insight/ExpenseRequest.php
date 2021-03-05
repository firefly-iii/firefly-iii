<?php


namespace FireflyIII\Api\V1\Requests\Insight;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * Class ExpenseRequest
 */
class ExpenseRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    private Collection $accounts;
    private Collection $budgets;
    private Collection $categories;

    /**
     * @return Carbon
     */
    public function getStart(): Carbon
    {
        $date = $this->date('start');
        $date->startOfDay();

        return $date;
    }

    /**
     * @return Carbon
     */
    public function getEnd(): Carbon
    {
        $date = $this->date('end');
        $date->endOfDay();

        return $date;
    }

    /**
     *
     */
    private function parseAccounts(): void
    {
        if (null === $this->accounts) {
            $this->accounts = new Collection;
        }
        if (0 !== $this->accounts->count()) {
            return;
        }
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array = $this->get('accounts');
        if (is_array($array)) {
            foreach ($array as $accountId) {
                $accountId = (int)$accountId;
                $account   = $repository->findNull($accountId);
                if (null !== $account) {
                    $this->accounts->push($account);
                }
            }
        }
    }

    /**
     *
     */
    private function parseBudgets(): void
    {
        if (null === $this->budgets) {
            $this->budgets = new Collection;
        }
        if (0 !== $this->budgets->count()) {
            return;
        }
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array = $this->get('budgets');
        if (is_array($array)) {
            foreach ($array as $budgetId) {
                $budgetId = (int)$budgetId;
                $budget   = $repository->findNull($budgetId);
                if (null !== $budgetId) {
                    $this->budgets->push($budget);
                }
            }
        }
    }

    /**
     * @return Collection
     */
    public function getBudgets(): Collection
    {
        $this->parseBudgets();

        return $this->budgets;
    }

    /**
     * @return Collection
     */
    public function getCategories(): Collection
    {
        $this->parseCategories();

        return $this->categories;
    }


    /**
     * @return Collection
     */
    public function getAssetAccounts(): Collection
    {
        $this->parseAccounts();
        $return = new Collection;
        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $type = $account->accountType->type;
            if (in_array($type, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE])) {
                $return->push($account);
            }
        }

        return $return;
    }

    /**
     * @return Collection
     */
    public function getExpenseAccounts(): Collection
    {
        $this->parseAccounts();
        $return = new Collection;
        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $type = $account->accountType->type;
            if (in_array($type, [AccountType::EXPENSE])) {
                $return->push($account);
            }
        }

        return $return;
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        return [
            'start' => $this->date('start'),
            'end'   => $this->date('end'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        // this is cheating but it works:
        $this->accounts = new Collection;
        $this->budgets  = new Collection;
        $this->categories = new Collection;

        return [
            'start' => 'required|date',
            'end'   => 'required|date|after:start',
        ];
    }

    /**
     *
     */
    private function parseCategories(): void
    {
        if (null === $this->categories) {
            $this->categories = new Collection;
        }
        if (0 !== $this->categories->count()) {
            return;
        }
        $repository = app(CategoryRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array = $this->get('categories');
        if (is_array($array)) {
            foreach ($array as $categoryId) {
                $categoryId = (int)$categoryId;
                $category   = $repository->findNull($categoryId);
                if (null !== $categoryId) {
                    $this->categories->push($category);
                }
            }
        }
    }
}