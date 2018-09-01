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
    /** @noinspection ClassOverridesFieldOfSuperClassInspection */
    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = ['transactions', 'piggy_banks', 'user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];
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
     * Include piggy banks into end result.
     *
     * @codeCoverageIgnore
     *
     * @param Account $account
     *
     * @return FractalCollection
     */
    public function includePiggyBanks(Account $account): FractalCollection
    {
        $piggies = $account->piggyBanks()->get();

        return $this->collection($piggies, new PiggyBankTransformer($this->parameters), 'piggy_banks');
    }

    /**
     * Include transactions into end result.
     *
     * @codeCoverageIgnore
     *
     * @param Account $account
     *
     * @return FractalCollection
     */
    public function includeTransactions(Account $account): FractalCollection
    {
        $pageSize = (int)app('preferences')->getForUser($account->user, 'listPageSize', 50)->data;

        // journals always use collector and limited using URL parameters.
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($account->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        if ($account->accountType->type === AccountType::ASSET) {
            $collector->setAccounts(new Collection([$account]));
        } else {
            $collector->setOpposingAccounts(new Collection([$account]));
        }
        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $transactions = $collector->getTransactions();

        return $this->collection($transactions, new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * Include user data in end result.
     *
     * @codeCoverageIgnore
     *
     * @param Account $account
     *
     * @return Item
     */
    public function includeUser(Account $account): Item
    {
        return $this->item($account->user, new UserTransformer($this->parameters), 'users');
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
        if ($type === AccountType::ASSET) {
            /** @var AccountRepositoryInterface $repository */
            $repository = app(AccountRepositoryInterface::class);
            $repository->setUser($account->user);
            $amount             = $repository->getOpeningBalanceAmount($account);
            $openingBalance     = null === $amount ? null : round($amount, $decimalPlaces);
            $openingBalanceDate = $repository->getOpeningBalanceDate($account);
        }

        $data = [
            'id'                   => (int)$account->id,
            'updated_at'           => $account->updated_at->toAtomString(),
            'created_at'           => $account->created_at->toAtomString(),
            'name'                 => $account->name,
            'active'               => 1 === (int)$account->active,
            'type'                 => $type,
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
            'iban'                 => $account->iban,
            'bic'                  => $this->repository->getMetaValue($account, 'BIC'),
            'virtual_balance'      => round($account->virtual_balance, $decimalPlaces),
            'opening_balance'      => $openingBalance,
            'opening_balance_date' => $openingBalanceDate,
            'role'                 => $role,
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
