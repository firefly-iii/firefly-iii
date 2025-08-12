<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PiggyBankEventEnrichment implements EnrichmentInterface
{
    private User       $user;
    private UserGroup  $userGroup;
    private Collection $collection;
    private array      $ids               = [];
    private array      $journalIds        = [];
    private array      $groupIds          = [];
    private array      $accountIds        = [];
    private array      $piggyBankIds      = [];
    private array      $accountCurrencies = [];
    private array      $currencies        = [];
    // private bool       $convertToPrimary  = false;
    // private TransactionCurrency $primaryCurrency;

    public function __construct()
    {
        // $this->convertToPrimary = Amount::convertToPrimary();
        // $this->primaryCurrency  = Amount::getPrimaryCurrency();
    }

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
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
        /** @var PiggyBankEvent $event */
        foreach ($this->collection as $event) {
            $this->ids[]                         = (int)$event->id;
            $this->journalIds[(int)$event->id]   = (int)$event->transaction_journal_id;
            $this->piggyBankIds[(int)$event->id] = (int)$event->piggy_bank_id;
        }
        $this->ids = array_unique($this->ids);
        // collect groups with journal info.
        $set       = TransactionJournal::whereIn('id', $this->journalIds)->get(['id', 'transaction_group_id']);

        /** @var TransactionJournal $item */
        foreach ($set as $item) {
            $this->groupIds[(int)$item->id] = (int)$item->transaction_group_id;
        }

        // collect account info.
        $set       = DB::table('account_piggy_bank')->whereIn('piggy_bank_id', $this->piggyBankIds)->get(['piggy_bank_id', 'account_id']);
        foreach ($set as $item) {
            $id = (int)$item->piggy_bank_id;
            if (!array_key_exists($id, $this->accountIds)) {
                $this->accountIds[$id] = (int)$item->account_id;
            }
        }

        // get account currency preference for ALL.
        $set       = AccountMeta::whereIn('account_id', array_values($this->accountIds))->where('name', 'currency_id')->get();

        /** @var AccountMeta $item */
        foreach ($set as $item) {
            $accountId                           = (int)$item->account_id;
            $currencyId                          = (int)$item->data;
            if (!array_key_exists($currencyId, $this->currencies)) {
                $this->currencies[$currencyId] = TransactionCurrency::find($currencyId);
            }
            $this->accountCurrencies[$accountId] = $this->currencies[$currencyId];
        }
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (PiggyBankEvent $item) {
            $id         = (int)$item->id;
            $piggyId    = (int)$item->piggy_bank_id;
            $journalId  = (int)$item->transaction_journal_id;
            $currency   = null;
            if (array_key_exists($piggyId, $this->accountIds)) {
                $accountId = $this->accountIds[$piggyId];
                if (array_key_exists($accountId, $this->accountCurrencies)) {
                    $currency = $this->accountCurrencies[$accountId];
                }
            }
            $meta       = [
                'transaction_group_id' => array_key_exists($journalId, $this->groupIds) ? (string)$this->groupIds[$journalId] : null,
                'currency'             => $currency,
            ];
            $item->meta = $meta;

            return $item;
        });

    }
}
