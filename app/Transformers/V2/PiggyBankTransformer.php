<?php

/**
 * PiggyBankTransformer.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers\V2;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class PiggyBankTransformer
 *
 * @deprecated
 */
class PiggyBankTransformer extends AbstractTransformer
{
    //    private AccountRepositoryInterface   $accountRepos;
    //    private CurrencyRepositoryInterface  $currencyRepos;
    //    private PiggyBankRepositoryInterface $piggyRepos;
    private array                 $accounts;
    private ExchangeRateConverter $converter;
    private array                 $currencies;
    private TransactionCurrency   $default;
    private array                 $groups;
    private array                 $notes;
    private array                 $repetitions;

    /**
     * PiggyBankTransformer constructor.
     */
    public function __construct()
    {
        $this->notes       = [];
        $this->accounts    = [];
        $this->groups      = [];
        $this->currencies  = [];
        $this->repetitions = [];
        //        $this->
        //        $this->currencyRepos = app(CurrencyRepositoryInterface::class);
        //        $this->piggyRepos    = app(PiggyBankRepositoryInterface::class);
    }

    public function collectMetaData(Collection $objects): Collection
    {
        // TODO move to repository (does not exist yet)
        $piggyBanks          = $objects->pluck('id')->toArray();
        $accountInfo         = Account::whereIn('id', $objects->pluck('account_id')->toArray())->get();
        $currencyPreferences = AccountMeta::where('name', '"currency_id"')->whereIn('account_id', $objects->pluck('account_id')->toArray())->get();
        $currencies          = [];

        /** @var Account $account */
        foreach ($accountInfo as $account) {
            $id                  = $account->id;
            $this->accounts[$id] = [
                'name' => $account->name,
            ];
        }

        /** @var AccountMeta $preference */
        foreach ($currencyPreferences as $preference) {
            $currencyId                   = (int) $preference->data;
            $accountId                    = $preference->account_id;
            $currencies[$currencyId] ??= TransactionJournal::find($currencyId);
            $this->currencies[$accountId] = $currencies[$currencyId];
        }

        // grab object groups:
        $set                 = DB::table('object_groupables')
            ->leftJoin('object_groups', 'object_groups.id', '=', 'object_groupables.object_group_id')
            ->where('object_groupables.object_groupable_type', PiggyBank::class)
            ->get(['object_groupables.*', 'object_groups.title', 'object_groups.order'])
        ;

        /** @var ObjectGroup $entry */
        foreach ($set as $entry) {
            $piggyBankId                = (int) $entry->object_groupable_id;
            $id                         = (int) $entry->object_group_id;
            $order                      = $entry->order;
            $this->groups[$piggyBankId] = [
                'object_group_id'    => (string) $id,
                'object_group_title' => $entry->title,
                'object_group_order' => $order,
            ];
        }

        // grab repetitions (for current amount):
        $repetitions         = PiggyBankRepetition::whereIn('piggy_bank_id', $piggyBanks)->get();
        if ('en_US' === config('app.fallback_locale')) {
            throw new FireflyException('[d] Piggy bank repetitions are EOL.');
        }

        /** @var PiggyBankRepetition $repetition */
        foreach ($repetitions as $repetition) {
            $this->repetitions[$repetition->piggy_bank_id] = [
                'amount' => $repetition->current_amount,
            ];
        }

        // grab notes
        // continue with notes
        $notes               = Note::whereNoteableType(PiggyBank::class)->whereIn('noteable_id', array_keys($piggyBanks))->get();

        /** @var Note $note */
        foreach ($notes as $note) {
            $id               = $note->noteable_id;
            $this->notes[$id] = $note;
        }

        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $this->default       = app('amount')->getNativeCurrencyByUserGroup(auth()->user()->userGroup);
        $this->converter     = new ExchangeRateConverter();

        return $objects;
    }

    /**
     * Transform the piggy bank.
     *
     * @throws FireflyException
     */
    public function transform(PiggyBank $piggyBank): array
    {
        //        $account = $piggyBank->account;
        //        $this->accountRepos->setUser($account->user);
        //        $this->currencyRepos->setUser($account->user);
        //        $this->piggyRepos->setUser($account->user);

        // get currency from account, or use default.
        //        $currency = $this->accountRepos->getAccountCurrency($account) ?? app('amount')->getNativeCurrencyByUser($account->user);

        // note
        //        $notes = $this->piggyRepos->getNoteText($piggyBank);
        //        $notes = '' === $notes ? null : $notes;

        //        $objectGroupId    = null;
        //        $objectGroupOrder = null;
        //        $objectGroupTitle = null;
        //        /** @var ObjectGroup $objectGroup */
        //        $objectGroup = $piggyBank->objectGroups->first();
        //        if (null !== $objectGroup) {
        //            $objectGroupId    = (int)$objectGroup->id;
        //            $objectGroupOrder = (int)$objectGroup->order;
        //            $objectGroupTitle = $objectGroup->title;
        //        }

        // get currently saved amount:
        //        $currentAmount = app('steam')->bcround($this->piggyRepos->getCurrentAmount($piggyBank), $currency->decimal_places);

        $percentage          = null;
        $leftToSave          = null;
        $nativeLeftToSave    = null;
        $savePerMonth        = null;
        $nativeSavePerMonth  = null;
        $startDate           = $piggyBank->start_date?->format('Y-m-d');
        $targetDate          = $piggyBank->target_date?->format('Y-m-d');
        $accountId           = $piggyBank->account_id;
        $accountName         = $this->accounts[$accountId]['name'] ?? null;
        $currency            = $this->currencies[$accountId] ?? $this->default;
        $currentAmount       = app('steam')->bcround($this->repetitions[$piggyBank->id]['amount'] ?? '0', $currency->decimal_places);
        $nativeCurrentAmount = $this->converter->convert($this->default, $currency, today(), $currentAmount);
        $targetAmount        = $piggyBank->target_amount;
        $nativeTargetAmount  = $this->converter->convert($this->default, $currency, today(), $targetAmount);
        $note                = $this->notes[$piggyBank->id] ?? null;
        $group               = $this->groups[$piggyBank->id] ?? null;

        if (0 !== bccomp($targetAmount, '0')) { // target amount is not 0.00
            $leftToSave         = bcsub($targetAmount, (string) $currentAmount);
            $nativeLeftToSave   = $this->converter->convert($this->default, $currency, today(), $leftToSave);
            $percentage         = (int) bcmul(bcdiv((string) $currentAmount, $targetAmount), '100');
            $savePerMonth       = $this->getSuggestedMonthlyAmount($currentAmount, $targetAmount, $piggyBank->start_date, $piggyBank->target_date);
            $nativeSavePerMonth = $this->converter->convert($this->default, $currency, today(), $savePerMonth);
        }
        $this->converter->summarize();

        return [
            'id'                             => (string) $piggyBank->id,
            'created_at'                     => $piggyBank->created_at->toAtomString(),
            'updated_at'                     => $piggyBank->updated_at->toAtomString(),
            'account_id'                     => (string) $piggyBank->account_id,
            'account_name'                   => $accountName,
            'name'                           => $piggyBank->name,
            'currency_id'                    => (string) $currency->id,
            'currency_code'                  => $currency->code,
            'currency_symbol'                => $currency->symbol,
            'currency_decimal_places'        => $currency->decimal_places,
            'native_currency_id'             => (string) $this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,
            'current_amount'                 => $currentAmount,
            'native_current_amount'          => $nativeCurrentAmount,
            'target_amount'                  => $targetAmount,
            'native_target_amount'           => $nativeTargetAmount,
            'percentage'                     => $percentage,
            'left_to_save'                   => $leftToSave,
            'native_left_to_save'            => $nativeLeftToSave,
            'save_per_month'                 => $savePerMonth,
            'native_save_per_month'          => $nativeSavePerMonth,
            'start_date'                     => $startDate,
            'target_date'                    => $targetDate,
            'order'                          => $piggyBank->order,
            'active'                         => $piggyBank->active,
            'notes'                          => $note,
            'object_group_id'                => $group ? $group['object_group_id'] : null,
            'object_group_order'             => $group ? $group['object_group_order'] : null,
            'object_group_title'             => $group ? $group['object_group_title'] : null,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_banks/'.$piggyBank->id,
                ],
            ],
        ];
    }

    private function getSuggestedMonthlyAmount(string $currentAmount, string $targetAmount, ?Carbon $startDate, ?Carbon $targetDate): string
    {
        $savePerMonth = '0';
        if (!$targetDate instanceof Carbon) {
            return '0';
        }
        if (bccomp($currentAmount, $targetAmount) < 1) {
            $now             = today(config('app.timezone'));
            $startDate       = $startDate instanceof Carbon && $startDate->gte($now) ? $startDate : $now;
            $diffInMonths    = (int) $startDate->diffInMonths($targetDate);
            $remainingAmount = bcsub($targetAmount, $currentAmount);

            // more than 1 month to go and still need money to save:
            if ($diffInMonths > 0 && 1 === bccomp($remainingAmount, '0')) {
                $savePerMonth = bcdiv($remainingAmount, (string) $diffInMonths);
            }

            // less than 1 month to go but still need money to save:
            if (0 === $diffInMonths && 1 === bccomp($remainingAmount, '0')) {
                $savePerMonth = $remainingAmount;
            }
        }

        return $savePerMonth;
    }
}
