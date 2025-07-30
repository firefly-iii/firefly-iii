<?php

namespace FireflyIII\Support\JsonApi\Enrichments;

use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionEnrichment implements EnrichmentInterface
{
    private User                $user;
    private UserGroup           $userGroup;
    private Collection          $collection;
    private bool                $convertToNative = false;
    private array $subscriptionIds = [];
    private array $objectGroups = [];
    private array $mappedObjects = [];
    private array $notes = [];
    private TransactionCurrency $nativeCurrency;

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectSubscriptionIds();
        $this->collectNotes();
        $this->collectObjectGroups();

        $notes = $this->notes;
        $objectGroups = $this->objectGroups;
        $this->collection = $this->collection->map(function (Bill $item) use ($notes, $objectGroups) {
            $id = (int) $item->id;
            $currency = $item->transactionCurrency;
            $meta = [
                'notes' => null,
                'object_group_id' => null,
                'object_group_title' => null,
                'object_group_order' => null,
            ];
            $amounts  = [
                'amount_min' => Steam::bcround($item->amount_min, $currency->decimal_places),
                'amount_max' => Steam::bcround($item->amount_max, $currency->decimal_places),
                'average'    => Steam::bcround(bcdiv(bcadd($item->amount_min, $item->amount_max), '2'), $currency->decimal_places),
            ];

            // add object group if available
            if (array_key_exists($id, $this->mappedObjects)) {
                $key = $this->mappedObjects[$id];
                $meta['object_group_id']    = $objectGroups[$key]['id'];
                $meta['object_group_title'] = $objectGroups[$key]['title'];
                $meta['object_group_order'] = $objectGroups[$key]['order'];
            }

            // Add notes if available.
            if (array_key_exists($item->id, $notes)) {
                $meta['notes'] = $notes[$item->id];
            }

            // Convert amounts to native currency if needed
            if ($this->convertToNative && $item->currency_id !== $this->nativeCurrency->id) {
                $converter          = new ExchangeRateConverter();
                $amounts            = [
                    'amount_min' => Steam::bcround($converter->convert($item->transactionCurrency, $this->nativeCurrency, today(), $item->amount_min), $this->nativeCurrency->decimal_places),
                    'amount_max' => Steam::bcround($converter->convert($item->transactionCurrency, $this->nativeCurrency, today(), $item->amount_max), $this->nativeCurrency->decimal_places),
                ];
                $amounts['average'] =Steam::bcround(bcdiv(bcadd($amounts['amount_min'], $amounts['amount_max']), '2'), $this->nativeCurrency->decimal_places);
            }
            $item->amounts = $amounts;
            $item->meta    = $meta;
            return $item;
        });

        return $collection;
    }

    public function enrichSingle(Model|array $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->subscriptionIds)
                     ->whereNotNull('notes.text')
                     ->where('notes.text', '!=', '')
                     ->where('noteable_type', Bill::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int) $note['noteable_id']] = (string) $note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
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

    public function setConvertToNative(bool $convertToNative): void
    {
        $this->convertToNative = $convertToNative;
    }

    public function setNative(TransactionCurrency $nativeCurrency): void
    {
        $this->nativeCurrency = $nativeCurrency;
    }
    private function collectSubscriptionIds(): void
    {
        /** @var Bill $bill */
        foreach ($this->collection as $bill) {
            $this->subscriptionIds[]     = (int) $bill->id;
        }
        $this->subscriptionIds     = array_unique($this->subscriptionIds);
    }

    private function collectObjectGroups(): void
    {
        $set = DB::table('object_groupables')
            ->whereIn('object_groupable_id', $this->subscriptionIds)
            ->where('object_groupable_type', Bill::class)
            ->get(['object_groupable_id','object_group_id']);

        $ids = array_unique($set->pluck('object_group_id')->toArray());

        foreach($set as $entry) {
            $this->mappedObjects[(int)$entry->object_groupable_id] = (int)$entry->object_group_id;
        }

        $groups = ObjectGroup::whereIn('id', $ids)->get(['id', 'title','order'])->toArray();
        foreach($groups as $group) {
            $group['id'] = (int) $group['id'];
            $group['order'] = (int) $group['order'];
            $this->objectGroups[(int)$group['id']] = $group;
        }
    }

}
