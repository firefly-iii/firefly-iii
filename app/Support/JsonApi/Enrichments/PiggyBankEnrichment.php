<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PiggyBankEnrichment implements EnrichmentInterface
{
    private User                $user;
    private UserGroup           $userGroup;
    private Collection          $collection;
    private array               $ids           = [];
    private array               $currencyIds   = [];
    private array               $currencies    = [];
    private array               $accountIds    = [];
    // private array               $accountCurrencies = [];
    private array               $notes         = [];
    private array               $mappedObjects = [];
    private TransactionCurrency $primaryCurrency;
    private array               $amounts       = [];

    public function __construct()
    {
        $this->primaryCurrency = Amount::getPrimaryCurrency();
    }

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectObjectGroups();
        $this->collectNotes();
        $this->collectCurrentAmounts();


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
        /** @var PiggyBank $piggy */
        foreach ($this->collection as $piggy) {
            $id                     = (int)$piggy->id;
            $this->ids[]            = $id;
            $this->currencyIds[$id] = (int)$piggy->transaction_currency_id;
        }
        $this->ids  = array_unique($this->ids);

        // collect currencies.
        $currencies = TransactionCurrency::whereIn('id', $this->currencyIds)->get();
        foreach ($currencies as $currency) {
            $this->currencies[(int)$currency->id] = $currency;
        }

        // collect accounts
        $set        = DB::table('account_piggy_bank')->whereIn('piggy_bank_id', $this->ids)->get(['piggy_bank_id', 'account_id', 'current_amount', 'native_current_amount']);
        foreach ($set as $item) {
            $id                                               = (int)$item->piggy_bank_id;
            $accountId                                        = (int)$item->account_id;
            $this->amounts[$id] ??= [];
            if (!array_key_exists($id, $this->accountIds)) {
                $this->accountIds[$id] = (int)$item->account_id;
            }
            if (!array_key_exists($accountId, $this->amounts[$id])) {
                $this->amounts[$id][$accountId] = [
                    'current_amount'    => '0',
                    'pc_current_amount' => '0',
                ];
            }
            $this->amounts[$id][$accountId]['current_amount'] = bcadd($this->amounts[$id][$accountId]['current_amount'], $item->current_amount);
            if (null !== $this->amounts[$id][$accountId]['pc_current_amount'] && null !== $item->native_current_amount) {
                $this->amounts[$id][$accountId]['pc_current_amount'] = bcadd($this->amounts[$id][$accountId]['pc_current_amount'], $item->native_current_amount);
            }
        }

        // get account currency preference for ALL.
        $set        = AccountMeta::whereIn('account_id', array_values($this->accountIds))->where('name', 'currency_id')->get();

        /** @var AccountMeta $item */
        foreach ($set as $item) {
            $accountId  = (int)$item->account_id;
            $currencyId = (int)$item->data;
            if (!array_key_exists($currencyId, $this->currencies)) {
                $this->currencies[$currencyId] = TransactionCurrency::find($currencyId);
            }
            // $this->accountCurrencies[$accountId] = $this->currencies[$currencyId];
        }

        // get account info.
        $set        = Account::whereIn('id', array_values($this->accountIds))->get();

        /** @var Account $item */
        foreach ($set as $item) {
            $id                  = (int)$item->id;
            $this->accounts[$id] = [
                'id'   => $id,
                'name' => $item->name,
            ];
        }
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (PiggyBank $item) {
            $id                        = (int)$item->id;
            $currencyId                = (int)$item->transaction_currency_id;
            $currency                  = $this->currencies[$currencyId] ?? $this->primaryCurrency;
            $targetAmount              = null;
            if (0 !== bccomp($item->target_amount, '0')) {
                $targetAmount = $item->target_amount;
            }
            $meta                      = [
                'notes'              => $this->notes[$id] ?? null,
                'currency'           => $this->currencies[$currencyId] ?? null,
                //                'auto_budget' => $this->autoBudgets[$id] ?? null,
                //                'spent'       => $this->spent[$id] ?? null,
                //                'pc_spent'    => $this->pcSpent[$id] ?? null,
                'object_group_id'    => null,
                'object_group_order' => null,
                'object_group_title' => null,
                'current_amount'     => '0',
                'pc_current_amount'  => '0',
                'target_amount'      => null === $targetAmount ? null : Steam::bcround($targetAmount, $currency->decimal_places),
                'pc_target_amount'   => null === $item->native_target_amount ? null : Steam::bcround($item->native_target_amount, $this->primaryCurrency->decimal_places),
                'left_to_save'       => null,
                'pc_left_to_save'    => null,
                'save_per_month'     => null,
                'pc_save_per_month'  => null,
                'accounts'           => [],
            ];

            // add object group if available
            if (array_key_exists($id, $this->mappedObjects)) {
                $key                        = $this->mappedObjects[$id];
                $meta['object_group_id']    = $this->objectGroups[$key]['id'];
                $meta['object_group_title'] = $this->objectGroups[$key]['title'];
                $meta['object_group_order'] = $this->objectGroups[$key]['order'];
            }
            // add current amount(s).
            foreach ($this->amounts[$id] as $accountId => $row) {
                $meta['accounts'][]        = [
                    'account_id'        => (string)$accountId,
                    'name'              => $this->accounts[$accountId]['name'] ?? '',
                    'current_amount'    => Steam::bcround($row['current_amount'], $currency->decimal_places),
                    'pc_current_amount' => Steam::bcround($row['pc_current_amount'], $this->primaryCurrency->decimal_places),
                ];
                $meta['current_amount']    = bcadd($meta['current_amount'], $row['current_amount']);
                // only add pc_current_amount when the pc_current_amount is set
                $meta['pc_current_amount'] = null === $row['pc_current_amount'] ? null : bcadd($meta['pc_current_amount'], $row['pc_current_amount']);
            }
            $meta['current_amount']    = Steam::bcround($meta['current_amount'], $currency->decimal_places);
            // only round this number when pc_current_amount is set.
            $meta['pc_current_amount'] = null === $meta['pc_current_amount'] ? null : Steam::bcround($meta['pc_current_amount'], $this->primaryCurrency->decimal_places);

            // calculate left to save, only when there is a target amount.
            if (null !== $targetAmount) {
                $meta['left_to_save']    = bcsub($meta['target_amount'], $meta['current_amount']);
                $meta['pc_left_to_save'] = null === $meta['pc_target_amount'] ? null : bcsub($meta['pc_target_amount'], $meta['pc_current_amount']);
            }

            // get suggested per month.
            $meta['save_per_month']    = Steam::bcround($this->getSuggestedMonthlyAmount($item->start_date, $item->target_date, $meta['target_amount'], $meta['current_amount']), $currency->decimal_places);
            $meta['pc_save_per_month'] = Steam::bcround($this->getSuggestedMonthlyAmount($item->start_date, $item->target_date, $meta['pc_target_amount'], $meta['pc_current_amount']), $currency->decimal_places);

            $item->meta                = $meta;

            return $item;
        });
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', PiggyBank::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function collectObjectGroups(): void
    {
        $set    = DB::table('object_groupables')
            ->whereIn('object_groupable_id', $this->ids)
            ->where('object_groupable_type', PiggyBank::class)
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

    private function collectCurrentAmounts(): void {}

    /**
     * Returns the suggested amount the user should save per month, or "".
     */
    private function getSuggestedMonthlyAmount(?Carbon $startDate, ?Carbon $targetDate, ?string $targetAmount, string $currentAmount): string
    {
        if (null === $targetAmount || null === $targetDate || null === $startDate) {
            return '0';
        }
        $savePerMonth = '0';
        if (1 === bccomp($targetAmount, $currentAmount)) {
            $now             = today(config('app.timezone'));
            $diffInMonths    = (int)$startDate->diffInMonths($targetDate);
            $remainingAmount = bcsub($targetAmount, $currentAmount);

            // more than 1 month to go and still need money to save:
            if ($diffInMonths > 0 && 1 === bccomp($remainingAmount, '0')) {
                $savePerMonth = bcdiv($remainingAmount, (string)$diffInMonths);
            }

            // less than 1 month to go but still need money to save:
            if (0 === $diffInMonths && 1 === bccomp($remainingAmount, '0')) {
                $savePerMonth = $remainingAmount;
            }
        }

        return $savePerMonth;
    }
}
