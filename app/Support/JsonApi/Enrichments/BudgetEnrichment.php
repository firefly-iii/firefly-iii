<?php

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BudgetEnrichment implements EnrichmentInterface
{
    private Collection          $collection;
    private bool                $convertToPrimary = true;
    private TransactionCurrency $primaryCurrency;
    private User                $user;
    private UserGroup           $userGroup;
    private array               $ids              = [];
    private array               $notes            = [];
    private array               $autoBudgets      = [];
    private array               $currencies       = [];
    private ?Carbon             $start            = null;
    private ?Carbon             $end              = null;
    private array               $spent            = [];
    private array               $pcSpent          = [];

    public function __construct()
    {
        $this->convertToPrimary = Amount::convertToPrimary();
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
    }


    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectNotes();
        $this->collectAutoBudgets();
        $this->collectExpenses();
        $this->appendCollectedData();

        return $this->collection;
    }

    public function enrichSingle(Model|array $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->setUserGroup($user->userGroup);
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    private function collectIds(): void
    {
        /** @var Budget $budget */
        foreach ($this->collection as $budget) {
            $this->ids[] = (int)$budget->id;
        }
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
                     ->whereNotNull('notes.text')
                     ->where('notes.text', '!=', '')
                     ->where('noteable_type', Budget::class)->get(['notes.noteable_id', 'notes.text'])->toArray();
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (Budget $item) {
            $id         = (int)$item->id;
            $meta       = [
                'notes'       => $this->notes[$id] ?? null,
                'currency'    => $this->currencies[$id] ?? null,
                'auto_budget' => $this->autoBudgets[$id] ?? null,
                'spent'     => $this->spent[$id] ?? null,
                'pc_spent'  => $this->pcSpent[$id] ?? null,
            ];
            $item->meta = $meta;
            return $item;
        });
    }

    private function collectAutoBudgets(): void
    {
        $set = AutoBudget::whereIn('budget_id', $this->ids)->with(['transactionCurrency'])->get();
        /** @var AutoBudget $autoBudget */
        foreach ($set as $autoBudget) {
            $budgetId                     = (int)$autoBudget->budget_id;
            $this->currencies[$budgetId]  = $autoBudget->transactionCurrency;
            $this->autoBudgets[$budgetId] = [
                'type'      => (int)$autoBudget->auto_budget_type,
                'period'    => $autoBudget->period,
                'amount'    => $autoBudget->amount,
                'pc_amount' => $autoBudget->native_amount,
            ];
        }
    }

    private function collectExpenses(): void
    {
        if (null !== $this->start && null !== $this->end) {
            /** @var OperationsRepositoryInterface $opsRepository */
            $opsRepository = app(OperationsRepositoryInterface::class);
            $opsRepository->setUser($this->user);
            $opsRepository->setUserGroup($this->userGroup);
            // $spent = $this->beautify();
            // $set = $this->opsRepository->sumExpenses($start, $end, null, new Collection([$budget]))
            $expenses = $opsRepository->collectExpenses($this->start, $this->end, null, $this->collection, null);
            foreach ($this->collection as $item) {
                $id                 = (int)$item->id;
                $this->spent[$id]   = array_values($opsRepository->sumCollectedExpensesByBudget($expenses, $item, false));
                $this->pcSpent[$id] = array_values($opsRepository->sumCollectedExpensesByBudget($expenses, $item, true));
            }
        }
    }

    public function setEnd(?Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(?Carbon $start): void
    {
        $this->start = $start;
    }


}
