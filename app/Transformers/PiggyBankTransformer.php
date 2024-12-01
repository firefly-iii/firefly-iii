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

namespace FireflyIII\Transformers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

/**
 * Class PiggyBankTransformer
 */
class PiggyBankTransformer extends AbstractTransformer
{
    private AccountRepositoryInterface   $accountRepos;
    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankTransformer constructor.
     */
    public function __construct()
    {
        $this->accountRepos = app(AccountRepositoryInterface::class);
        $this->piggyRepos   = app(PiggyBankRepositoryInterface::class);
    }

    /**
     * Transform the piggy bank.
     *
     * @throws FireflyException
     */
    public function transform(PiggyBank $piggyBank): array
    {
        $user = $piggyBank->accounts()->first()->user;

        // set up repositories
        $this->accountRepos->setUser($user);
        $this->piggyRepos->setUser($user);

        // note
        $notes = $this->piggyRepos->getNoteText($piggyBank);
        $notes = '' === $notes ? null : $notes;

        $objectGroupId    = null;
        $objectGroupOrder = null;
        $objectGroupTitle = null;

        /** @var null|ObjectGroup $objectGroup */
        $objectGroup = $piggyBank->objectGroups->first();
        if (null !== $objectGroup) {
            $objectGroupId    = $objectGroup->id;
            $objectGroupOrder = $objectGroup->order;
            $objectGroupTitle = $objectGroup->title;
        }

        // get currently saved amount:
        $currency      = $piggyBank->transactionCurrency;
        $currentAmount = app('steam')->bcround($this->piggyRepos->getCurrentAmount($piggyBank), $currency->decimal_places);

        // Amounts, depending on 0.0 state of target amount
        $percentage   = null;
        $targetAmount = $piggyBank->target_amount;
        $leftToSave   = null;
        $savePerMonth = null;
        if (0 !== bccomp($targetAmount, '0')) { // target amount is not 0.00
            $leftToSave   = bcsub($piggyBank->target_amount, $currentAmount);
            $percentage   = (int) bcmul(bcdiv($currentAmount, $targetAmount), '100');
            $targetAmount = app('steam')->bcround($targetAmount, $currency->decimal_places);
            $leftToSave   = app('steam')->bcround($leftToSave, $currency->decimal_places);
            $savePerMonth = app('steam')->bcround($this->piggyRepos->getSuggestedMonthlyAmount($piggyBank), $currency->decimal_places);
        }
        $startDate  = $piggyBank->start_date?->format('Y-m-d');
        $targetDate = $piggyBank->target_date?->format('Y-m-d');

        return [
            'id'                      => (string) $piggyBank->id,
            'created_at'              => $piggyBank->created_at->toAtomString(),
            'updated_at'              => $piggyBank->updated_at->toAtomString(),
            'accounts'                => $this->renderAccounts($piggyBank),
            //'account_id'              => (string)$piggyBank->account_id,
            //'account_name'            => $piggyBank->account->name,
            'name'                    => $piggyBank->name,
            'currency_id'             => (string) $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,
            'target_amount'           => $targetAmount,
            'percentage'              => $percentage,
            'current_amount'          => $currentAmount,
            'left_to_save'            => $leftToSave,
            'save_per_month'          => $savePerMonth,
            'start_date'              => $startDate,
            'target_date'             => $targetDate,
            'order'                   => $piggyBank->order,
            'active'                  => true,
            'notes'                   => $notes,
            'object_group_id'         => null !== $objectGroupId ? (string) $objectGroupId : null,
            'object_group_order'      => $objectGroupOrder,
            'object_group_title'      => $objectGroupTitle,
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_banks/' . $piggyBank->id,
                ],
            ],
        ];
    }

    private function renderAccounts(PiggyBank $piggyBank): array
    {
        $return = [];
        foreach ($piggyBank->accounts as $account) {
            $return[] = [
                'id'   => $account->id,
                'name' => $account->name,
                // TODO add balance, add left to save.
            ];
        }
        return $return;
    }
}
