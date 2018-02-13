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
use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
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
    protected $availableIncludes = ['journals', 'piggy_banks', 'user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];
    /** @var ParameterBag */
    protected $parameters;

    /**
     * BillTransformer constructor.
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param Account $account
     *
     * @return FractalCollection
     */
    public function includeJournals(Account $account): FractalCollection
    {
        $ids   = $account->transactions()->get(['transactions.transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
        $query = TransactionJournal::whereIn('id', $ids);
        if (!is_null($this->parameters->get('end'))) {
            $query->where('date', '<=', $this->parameters->get('end')->format('Y-m-d 00:00:00'));
        }
        if (!is_null($this->parameters->get('start'))) {
            $query->where('date', '>=', $this->parameters->get('start')->format('Y-m-d 00:00:00'));
        }

        $journals = $query->get(['transaction_journals.*']);

        return $this->collection($journals, new TransactionJournalTransformer($this->parameters), 'journals');
    }

    /**
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
     * @param Account $account
     *
     * @return Item
     */
    public function includeUser(Account $account): Item
    {
        return $this->item($account->user, new UserTransformer($this->parameters), 'user');
    }

    /**
     * @param Account $account
     *
     * @return array
     */
    public function transform(Account $account): array
    {
        $role = $account->getMeta('accountRole');
        if (strlen($role) === 0) {
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

        $data = [
            'id'                   => (int)$account->id,
            'updated_at'           => $account->updated_at->toAtomString(),
            'created_at'           => $account->created_at->toAtomString(),
            'name'                 => $account->name,
            'active'               => intval($account->active) === 1,
            'type'                 => $account->accountType->type,
            'currency_id'          => $currencyId,
            'currency_code'        => $currencyCode,
            'current_balance'      => round(app('steam')->balance($account, $date), $decimalPlaces),
            'current_balance_date' => $date->format('Y-m-d'),
            'notes'                => null,
            'monthly_payment_date' => $this->getMeta($account, 'ccMonthlyPaymentDate'),
            'credit_card_type'     => $this->getMeta($account, 'ccType'),
            'account_number'       => $this->getMeta($account, 'accountNumber'),
            'iban'                 => $account->iban,
            'bic'                  => $this->getMeta($account, 'BIC'),
            'virtual_balance'      => round($account->virtual_balance, $decimalPlaces),
            'role'                 => $role,
            'links'                => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];

        // todo opening balance
        /** @var Note $note */
        $note = $account->notes()->first();
        if (!is_null($note)) {
            $data['notes'] = $note->text;
        }

        return $data;
    }

    /**
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