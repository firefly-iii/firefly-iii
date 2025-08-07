<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetEnrichment implements EnrichmentInterface
{
    private Collection          $collection;
    private bool                $convertToPrimary;
    private TransactionCurrency $primaryCurrency;
    private User                $user;
    private UserGroup           $userGroup;
    private array               $ids           = [];
    private array               $notes         = [];
    private array               $autoBudgets   = [];
    private array               $currencies    = [];
    private ?Carbon             $start         = null;
    private ?Carbon             $end           = null;
    private array               $spent         = [];
    private array               $pcSpent       = [];
    private array               $objectGroups  = [];
    private array               $mappedObjects = [];

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
        $this->collectObjectGroups();

        $this->appendCollectedData();

        return $this->collection;
    }

    public function enrichSingle(array|Model $model): array|Model
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
        $this->ids = array_unique($this->ids);
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', Budget::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
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
                'object_group_id'    => null,
                'object_group_order' => null,
                'object_group_title' => null,
                'notes'              => $this->notes[$id] ?? null,
                'currency'           => $this->currencies[$id] ?? null,
                'auto_budget'        => $this->autoBudgets[$id] ?? null,
                'spent'              => $this->spent[$id] ?? null,
                'pc_spent'           => $this->pcSpent[$id] ?? null,
            ];

            // add object group if available
            if (array_key_exists($id, $this->mappedObjects)) {
                $key                        = $this->mappedObjects[$id];
                $meta['object_group_id']    = $this->objectGroups[$key]['id'];
                $meta['object_group_title'] = $this->objectGroups[$key]['title'];
                $meta['object_group_order'] = $this->objectGroups[$key]['order'];
            }


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
            $expenses      = $opsRepository->collectExpenses($this->start, $this->end, null, $this->collection, null);
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

    private function collectObjectGroups(): void
    {
        $set    = DB::table('object_groupables')
            ->whereIn('object_groupable_id', $this->ids)
            ->where('object_groupable_type', Budget::class)
            ->get(['object_groupable_id', 'object_group_id'])
        ;

        $ids    = array_unique($set->pluck('object_group_id')->toArray());

        foreach ($set as $entry) {
            $this->mappedObjects[(int)$entry->object_groupable_id] = (int)$entry->object_group_id;
        }

        $groups = ObjectGroup::whereIn('id', $ids)->get(['id', 'title', 'order'])->toArray();
        foreach ($groups as $group) {
            $group['id']                           = (int)$group['id'];
            $group['order']                        = (int)$group['order'];
            $this->objectGroups[(int)$group['id']] = $group;
        }
    }
}
