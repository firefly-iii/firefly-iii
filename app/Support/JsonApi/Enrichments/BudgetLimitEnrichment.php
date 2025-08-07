<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Budget\OperationsRepository;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BudgetLimitEnrichment implements EnrichmentInterface
{
    private User                $user;
    private UserGroup           $userGroup;
    private Collection          $collection;
    private array               $ids              = [];
    private array               $notes            = [];
    private Carbon              $start;
    private Carbon              $end;
    private Collection          $budgets;
    private array               $expenses         = [];
    private array               $pcExpenses       = [];
    private array               $currencyIds      = [];
    private array               $currencies       = [];
    private bool                $convertToPrimary = true;
    private TransactionCurrency $primaryCurrency;

    public function __construct()
    {
        $this->convertToPrimary = Amount::convertToPrimary();
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
    }

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectCurrencies();
        $this->collectNotes();
        $this->collectBudgets();
        $this->appendCollectedData();

        return $this->collection;
    }

    public function enrichSingle(array|Model $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection()->push($model);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    public function setUser(User $user): void
    {
        $this->user      = $user;
        $this->userGroup = $user->userGroup;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    private function collectIds(): void
    {
        $this->start       = $this->collection->min('start_date');
        $this->end         = $this->collection->max('end_date');

        /** @var BudgetLimit $limit */
        foreach ($this->collection as $limit) {
            $id          = (int)$limit->id;
            $this->ids[] = $id;
            if (0 !== (int)$limit->transaction_currency_id) {
                $this->currencyIds[$id] = (int)$limit->transaction_currency_id;
            }
        }
        $this->ids         = array_unique($this->ids);
        $this->currencyIds = array_unique($this->currencyIds);
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', BudgetLimit::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (BudgetLimit $item) {
            $id         = (int)$item->id;
            $currencyId = (int)$item->transaction_currency_id;
            if (0 === $currencyId) {
                $currencyId = $this->primaryCurrency->id;
            }
            $meta       = [
                'notes'    => $this->notes[$id] ?? null,
                'spent'    => $this->expenses[$id] ?? [],
                'pc_spent' => $this->pcExpenses[$id] ?? [],
                'currency' => $this->currencies[$currencyId],
            ];
            $item->meta = $meta;

            return $item;
        });
    }

    private function collectBudgets(): void
    {
        $budgetIds     = $this->collection->pluck('budget_id')->unique()->toArray();
        $this->budgets = Budget::whereIn('id', $budgetIds)->get();

        $repository    = app(OperationsRepository::class);
        $repository->setUser($this->user);
        $expenses      = $repository->collectExpenses($this->start, $this->end, null, $this->budgets, null);

        /** @var BudgetLimit $budgetLimit */
        foreach ($this->collection as $budgetLimit) {
            $id                  = (int)$budgetLimit->id;
            $filteredExpenses    = $repository->sumCollectedExpenses($expenses, $budgetLimit->start_date, $budgetLimit->end_date, $budgetLimit->transactionCurrency, false);
            $this->expenses[$id] = array_values($filteredExpenses);

            if (true === $this->convertToPrimary && $budgetLimit->transactionCurrency->id !== $this->primaryCurrency->id) {
                $pcFilteredExpenses    = $repository->sumCollectedExpenses($expenses, $budgetLimit->start_date, $budgetLimit->end_date, $budgetLimit->transactionCurrency, true);
                $this->pcExpenses[$id] = array_values($pcFilteredExpenses);
            }
            if (true === $this->convertToPrimary && $budgetLimit->transactionCurrency->id === $this->primaryCurrency->id) {
                $this->pcExpenses[$id] = $this->expenses[$id] ?? [];
            }
        }
    }

    private function collectCurrencies(): void
    {
        $this->currencies[$this->primaryCurrency->id] = $this->primaryCurrency;
        $currencies                                   = TransactionCurrency::whereIn('id', $this->currencyIds)->whereNot('id', $this->primaryCurrency->id)->get();
        foreach ($currencies as $currency) {
            $this->currencies[(int)$currency->id] = $currency;
        }
    }
}
