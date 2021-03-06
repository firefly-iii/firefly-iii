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


use FireflyIII\Models\Account;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

/**
 * Class PiggyBankTransformer
 */
class PiggyBankTransformer extends AbstractTransformer
{
    private AccountRepositoryInterface $accountRepos;
    private CurrencyRepositoryInterface $currencyRepos;
    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->accountRepos  = app(AccountRepositoryInterface::class);
        $this->currencyRepos = app(CurrencyRepositoryInterface::class);
        $this->piggyRepos    = app(PiggyBankRepositoryInterface::class);
    }


    /**
     * Transform the piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return array
     */
    public function transform(PiggyBank $piggyBank): array
    {
        /** @var Account $account */
        $account = $piggyBank->account;

        // set up repositories
        $this->accountRepos->setUser($account->user);
        $this->currencyRepos->setUser($account->user);
        $this->piggyRepos->setUser($account->user);

        // get currency from account, or use default.
        $currency = $this->accountRepos->getAccountCurrency($account) ?? app('amount')->getDefaultCurrencyByUser($account->user);

        // note
        $notes = $this->piggyRepos->getNoteText($piggyBank);
        $notes = '' === $notes ? null : $notes;

        $objectGroupId    = null;
        $objectGroupOrder = null;
        $objectGroupTitle = null;
        /** @var ObjectGroup $objectGroup */
        $objectGroup = $piggyBank->objectGroups->first();
        if (null !== $objectGroup) {
            $objectGroupId    = (int) $objectGroup->id;
            $objectGroupOrder = (int) $objectGroup->order;
            $objectGroupTitle = $objectGroup->title;
        }

        // get currently saved amount:
        $currentAmountStr = $this->piggyRepos->getCurrentAmount($piggyBank);
        $currentAmount    = number_format((float) $currentAmountStr, $currency->decimal_places, '.', '');

        // left to save:
        $leftToSave = bcsub($piggyBank->targetamount, $currentAmountStr);
        $startDate  = null === $piggyBank->startdate ? null : $piggyBank->startdate->format('Y-m-d');
        $targetDate = null === $piggyBank->targetdate ? null : $piggyBank->targetdate->format('Y-m-d');

        // target and percentage:
        $targetAmount = $piggyBank->targetamount;
        $targetAmount = 1 === bccomp('0.01', (string) $targetAmount) ? '0.01' : $targetAmount;
        $percentage   = (int) (0 !== bccomp('0', $currentAmountStr) ? $currentAmountStr / $targetAmount * 100 : 0);
        return [
            'id'                      => (string) $piggyBank->id,
            'created_at'              => $piggyBank->created_at->toAtomString(),
            'updated_at'              => $piggyBank->updated_at->toAtomString(),
            'account_id'              => (string) $piggyBank->account_id,
            'account_name'            => $piggyBank->account->name,
            'name'                    => $piggyBank->name,
            'currency_id'             => (string) $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => (int) $currency->decimal_places,
            'target_amount'           => number_format((float) $targetAmount, $currency->decimal_places, '.', ''),
            'percentage'              => $percentage,
            'current_amount'          => $currentAmount,
            'left_to_save'            => number_format((float) $leftToSave, $currency->decimal_places, '.', ''),
            'save_per_month'          => number_format((float) $this->piggyRepos->getSuggestedMonthlyAmount($piggyBank), $currency->decimal_places, '.', ''),
            'start_date'              => $startDate,
            'target_date'             => $targetDate,
            'order'                   => (int) $piggyBank->order,
            'active'                  => true,
            'notes'                   => $notes,
            'object_group_id'         => $objectGroupId ? (string)$objectGroupId : null,
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
}
