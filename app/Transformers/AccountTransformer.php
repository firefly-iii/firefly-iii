<?php
/**
 * AccountTransformer.php
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


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /** @var AccountRepositoryInterface */
    protected $repository;

    /**
     *
     * AccountTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->repository = app(AccountRepositoryInterface::class);
        $this->parameters = $parameters;
    }

    /**
     * Transform the account.
     *
     * @param Account $account
     *
     * @return array
     */
    public function transform(Account $account): array
    {
        $this->repository->setUser($account->user);

        $type = $account->accountType->type;
        $role = $this->repository->getMetaValue($account, 'accountRole');
        if ($type !== AccountType::ASSET || '' === (string)$role) {
            $role = null;
        }
        $currencyId     = (int)$this->repository->getMetaValue($account, 'currency_id');
        $currencyCode   = null;
        $currencySymbol = null;
        $decimalPlaces  = 2;
        if ($currencyId > 0) {
            $currency       = TransactionCurrency::find($currencyId);
            $currencyCode   = $currency->code;
            $decimalPlaces  = $currency->decimal_places;
            $currencySymbol = $currency->symbol;
        }

        $date = new Carbon;
        if (null !== $this->parameters->get('date')) {
            $date = $this->parameters->get('date');
        }

        if (0 === $currencyId) {
            $currencyId = null;
        }

        $monthlyPaymentDate = null;
        $creditCardType     = null;
        if ('ccAsset' === $role && $type === AccountType::ASSET) {
            $creditCardType     = $this->repository->getMetaValue($account, 'ccType');
            $monthlyPaymentDate = $this->repository->getMetaValue($account, 'ccMonthlyPaymentDate');
        }

        $openingBalance     = null;
        $openingBalanceDate = null;
        if (\in_array($type, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE], true)) {
            $amount             = $this->repository->getOpeningBalanceAmount($account);
            $openingBalance     = null === $amount ? null : round($amount, $decimalPlaces);
            $openingBalanceDate = $this->repository->getOpeningBalanceDate($account);
        }
        $interest        = $this->repository->getMetaValue($account, 'interest');
        $interestPeriod  = $this->repository->getMetaValue($account, 'interest_period');
        $includeNetworth = '0' !== $this->repository->getMetaValue($account, 'include_net_worth');

        $data = [
            'id'                   => (int)$account->id,
            'updated_at'           => $account->updated_at->toAtomString(),
            'created_at'           => $account->created_at->toAtomString(),
            'active'               => 1 === (int)$account->active,
            'name'                 => $account->name,
            'type'                 => $type,
            'account_role'         => $role,
            'currency_id'          => $currencyId,
            'currency_code'        => $currencyCode,
            'currency_symbol'      => $currencySymbol,
            'currency_dp'          => $decimalPlaces,
            'current_balance'      => round(app('steam')->balance($account, $date), $decimalPlaces),
            'current_balance_date' => $date->format('Y-m-d'),
            'notes'                => $this->repository->getNoteText($account),
            'monthly_payment_date' => $monthlyPaymentDate,
            'credit_card_type'     => $creditCardType,
            'account_number'       => $this->repository->getMetaValue($account, 'accountNumber'),
            'iban'                 => '' === $account->iban ? null : $account->iban,
            'bic'                  => $this->repository->getMetaValue($account, 'BIC'),
            'virtual_balance'      => round($account->virtual_balance, $decimalPlaces),
            'opening_balance'      => $openingBalance,
            'opening_balance_date' => $openingBalanceDate,
            'liability_type'       => $type,
            'liability_amount'     => $openingBalance,
            'liability_start_date' => $openingBalanceDate,
            'interest'             => $interest,
            'interest_period'      => $interestPeriod,
            'include_net_worth'    => $includeNetworth,
            'links'                => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];

        return $data;
    }
}
