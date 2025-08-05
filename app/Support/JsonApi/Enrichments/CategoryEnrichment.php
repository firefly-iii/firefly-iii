<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Models\Note;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CategoryEnrichment implements EnrichmentInterface
{
    private Collection $collection;
    private User       $user;
    private UserGroup  $userGroup;
    private array      $ids         = [];
    private array      $notes       = [];
    private ?Carbon    $start       = null;
    private ?Carbon    $end         = null;
    private array      $spent       = [];
    private array      $pcSpent     = [];
    private array      $earned      = [];
    private array      $pcEarned    = [];
    private array      $transfers   = [];
    private array      $pcTransfers = [];

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectNotes();
        $this->collectTransactions();
        $this->appendCollectedData();

        return $collection;
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
        /** @var Category $category */
        foreach ($this->collection as $category) {
            $this->ids[] = (int)$category->id;
        }
        $this->ids = array_unique($this->ids);
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (Category $item) {
            $id         = (int)$item->id;
            $meta       = [
                'notes'        => $this->notes[$id] ?? null,
                'spent'        => $this->spent[$id] ?? null,
                'pc_spent'     => $this->pcSpent[$id] ?? null,
                'earned'       => $this->earned[$id] ?? null,
                'pc_earned'    => $this->pcEarned[$id] ?? null,
                'transfers'    => $this->transfers[$id] ?? null,
                'pc_transfers' => $this->pcTransfers[$id] ?? null,
            ];
            $item->meta = $meta;

            return $item;
        });
    }

    public function setEnd(?Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(?Carbon $start): void
    {
        $this->start = $start;
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', Category::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function collectTransactions(): void
    {
        if (null !== $this->start && null !== $this->end) {
            /** @var OperationsRepositoryInterface $opsRepository */
            $opsRepository = app(OperationsRepositoryInterface::class);
            $opsRepository->setUser($this->user);
            $opsRepository->setUserGroup($this->userGroup);
            $expenses      = $opsRepository->collectExpenses($this->start, $this->end, null, $this->collection);
            $income        = $opsRepository->collectIncome($this->start, $this->end, null, $this->collection);
            $transfers     = $opsRepository->collectTransfers($this->start, $this->end, null, $this->collection);
            foreach ($this->collection as $item) {
                $id                     = (int)$item->id;
                $this->spent[$id]       = array_values($opsRepository->sumCollectedTransactionsByCategory($expenses, $item, 'negative', false));
                $this->pcSpent[$id]     = array_values($opsRepository->sumCollectedTransactionsByCategory($expenses, $item, 'negative', true));
                $this->earned[$id]      = array_values($opsRepository->sumCollectedTransactionsByCategory($income, $item, 'positive', false));
                $this->pcEarned[$id]    = array_values($opsRepository->sumCollectedTransactionsByCategory($income, $item, 'positive', true));
                $this->transfers[$id]   = array_values($opsRepository->sumCollectedTransactionsByCategory($transfers, $item, 'positive', false));
                $this->pcTransfers[$id] = array_values($opsRepository->sumCollectedTransactionsByCategory($transfers, $item, 'positive', true));
            }
        }
    }
}
