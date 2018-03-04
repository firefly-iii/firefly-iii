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
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
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
        $pageSize = intval(app('preferences')->getForUser($account->user, 'listPageSize', 50)->data);

        // journals always use collector and limited using URL parameters.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($account->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        if ($account->accountType->type === AccountType::ASSET) {
            $collector->setAccounts(new Collection([$account]));
        } else {
            $collector->setOpposingAccounts(new Collection([$account]));
        }
        if (!is_null($this->parameters->get('start')) && !is_null($this->parameters->get('end'))) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $journals = $collector->getJournals();

        return $this->collection($journals, new TransactionTransformer($this->parameters), 'transactions');
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
        return $this->item($account->user, new UserTransformer($this->parameters), 'user');
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
        $type = $account->accountType->type;
        $role = $account->getMeta('accountRole');
        if (strlen($role) === 0 || $type !== AccountType::ASSET) {
            $role = null;
        }
        $currencyId    = (int)$account->getMeta('currency_id');
        $currencyCode  = null;
        $decimalPlaces = 2;
        if ($currencyId > 0) {
            $currency      = TransactionCurrency::find($currencyId);
            $currencyCode  = $currency->code;
            $decimalPlaces = $currency->decimal_places;
        }

        $date = new Carbon;
        if (!is_null($this->parameters->get('date'))) {
            $date = $this->parameters->get('date');
        }

        if ($currencyId === 0) {
            $currencyId = null;
        }

        $monthlyPaymentDate = null;
        $creditCardType     = null;
        if ($role === 'ccAsset' && $type === AccountType::ASSET) {
            $creditCardType     = $this->getMeta($account, 'ccType');
            $monthlyPaymentDate = $this->getMeta($account, 'ccMonthlyPaymentDate');
        }

        $openingBalance     = null;
        $openingBalanceDate = null;
        if ($type === AccountType::ASSET) {
            /** @var AccountRepositoryInterface $repository */
            $repository = app(AccountRepositoryInterface::class);
            $repository->setuser($account->user);
            $amount             = $repository->getOpeningBalanceAmount($account);
            $openingBalance     = is_null($amount) ? null : round($amount, $decimalPlaces);
            $openingBalanceDate = $repository->getOpeningBalanceDate($account);
        }

        $data = [
            'id'                   => (int)$account->id,
            'updated_at'           => $account->updated_at->toAtomString(),
            'created_at'           => $account->created_at->toAtomString(),
            'name'                 => $account->name,
            'active'               => intval($account->active) === 1,
            'type'                 => $type,
            'currency_id'          => $currencyId,
            'currency_code'        => $currencyCode,
            'current_balance'      => round(app('steam')->balance($account, $date), $decimalPlaces),
            'current_balance_date' => $date->format('Y-m-d'),
            'notes'                => null,
            'monthly_payment_date' => $monthlyPaymentDate,
            'credit_card_type'     => $creditCardType,
            'account_number'       => $this->getMeta($account, 'accountNumber'),
            'iban'                 => $account->iban,
            'bic'                  => $this->getMeta($account, 'BIC'),
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

        /** @var Note $note */
        $note = $account->notes()->first();
        if (!is_null($note)) {
            $data['notes'] = $note->text; // @codeCoverageIgnore
        }

        return $data;
    }

    /**
     * Get meta data field for account.
     *
     * @codeCoverageIgnore
     *
     * @param Account $account
     * @param string  $field
     *
     * @return null|string
     */
    private function getMeta(Account $account, string $field): ?string
    {
        $result = $account->getMeta($field);
        if (strlen($result) === 0) {
            return null;
        }

        return $result;
    }

}