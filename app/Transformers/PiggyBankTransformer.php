<?php
/**
 * PiggyBankTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;


use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Log;

/**
 * Class PiggyBankTransformer
 */
class PiggyBankTransformer extends AbstractTransformer
{
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var PiggyBankRepositoryInterface */
    private $piggyRepos;

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
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
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
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $currency   = $this->currencyRepos->findNull($currencyId);
        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrencyByUser($account->user);
        }

        // note
        $notes = $this->piggyRepos->getNoteText($piggyBank);
        $notes = '' === $notes ? null : $notes;

        // get currently saved amount:
        $currentAmountStr = $this->piggyRepos->getCurrentAmount($piggyBank);
        $currentAmount    = round($currentAmountStr, $currency->decimal_places);

        // left to save:
        $leftToSave = bcsub($piggyBank->targetamount, $currentAmountStr);
        $startDate  = null === $piggyBank->startdate ? null : $piggyBank->startdate->format('Y-m-d');
        $targetDate = null === $piggyBank->targetdate ? null : $piggyBank->targetdate->format('Y-m-d');

        // target and percentage:
        $targetAmount = round($piggyBank->targetamount, $currency->decimal_places);
        $percentage   = (int)(0 !== bccomp('0', $currentAmountStr) ? $currentAmount / $targetAmount * 100 : 0);
        $data         = [
            'id'                      => (int)$piggyBank->id,
            'created_at'              => $piggyBank->created_at->toAtomString(),
            'updated_at'              => $piggyBank->updated_at->toAtomString(),
            'account_id'              => $piggyBank->account_id,
            'account_name'            => $piggyBank->account->name,
            'name'                    => $piggyBank->name,
            'currency_id'             => $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,
            'target_amount'           => $targetAmount,
            'percentage'              => $percentage,
            'current_amount'          => $currentAmount,
            'left_to_save'            => round($leftToSave, $currency->decimal_places),
            'save_per_month'          => round($this->piggyRepos->getSuggestedMonthlyAmount($piggyBank), $currency->decimal_places),
            'start_date'              => $startDate,
            'target_date'             => $targetDate,
            'order'                   => (int)$piggyBank->order,
            'active'                  => true,
            'notes'                   => $notes,
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_banks/' . $piggyBank->id,
                ],
            ],
        ];

        return $data;
    }
}
