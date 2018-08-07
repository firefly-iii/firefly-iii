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
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class PiggyBankTransformer
 */
class PiggyBankTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['account', 'user', 'piggy_bank_events'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * PiggyBankTransformer constructor.
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
     * Include account.
     *
     * @codeCoverageIgnore
     *
     * @param PiggyBank $piggyBank
     *
     * @return Item
     */
    public function includeAccount(PiggyBank $piggyBank): Item
    {
        return $this->item($piggyBank->account, new AccountTransformer($this->parameters), 'accounts');
    }

    /**
     * Include events.
     *
     * @codeCoverageIgnore
     *
     * @param PiggyBank $piggyBank
     *
     * @return FractalCollection
     */
    public function includePiggyBankEvents(PiggyBank $piggyBank): FractalCollection
    {
        return $this->collection($piggyBank->piggyBankEvents, new PiggyBankEventTransformer($this->parameters), 'piggy_bank_events');
    }

    /**
     * Include the user.
     *
     * @param PiggyBank $piggyBank
     *
     * @codeCoverageIgnore
     * @return Item
     */
    public function includeUser(PiggyBank $piggyBank): Item
    {
        return $this->item($piggyBank->account->user, new UserTransformer($this->parameters), 'users');
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
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($account->user);
        $currencyId = (int)$accountRepos->getMetaValue($account, 'currency_id');
        $currency   = null;
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrencyByUser($account->user);
        }

        if ($currencyId > 0) {
            /** @var CurrencyRepositoryInterface $repository */
            $repository = app(CurrencyRepositoryInterface::class);
            $repository->setUser($account->user);
            $currency = $repository->findNull($currencyId);
        }

        $decimalPlaces = $currency->decimal_places;

        // get currently saved amount:
        /** @var PiggyBankRepositoryInterface $piggyRepos */
        $piggyRepos = app(PiggyBankRepositoryInterface::class);
        $piggyRepos->setUser($account->user);

        // current amount in piggy bank:
        $currentAmountStr = $piggyRepos->getCurrentAmount($piggyBank);
        $currentAmount    = round($currentAmountStr, $decimalPlaces);

        // left to save to target:
        $leftToSave   = bcsub($piggyBank->targetamount, $currentAmountStr);
        $startDate    = null === $piggyBank->startdate ? null : $piggyBank->startdate->format('Y-m-d');
        $targetDate   = null === $piggyBank->targetdate ? null : $piggyBank->targetdate->format('Y-m-d');
        $targetAmount = round($piggyBank->targetamount, $decimalPlaces);
        $percentage   = (int)(0 !== bccomp('0', $currentAmountStr) ? $currentAmount / $targetAmount * 100 : 0);
        $data         = [
            'id'              => (int)$piggyBank->id,
            'updated_at'      => $piggyBank->updated_at->toAtomString(),
            'created_at'      => $piggyBank->created_at->toAtomString(),
            'name'            => $piggyBank->name,
            'currency_id'     => $currency->id,
            'currency_code'   => $currency->code,
            'currency_symbol' => $currency->symbol,
            'currency_dp'     => $currency->decimal_places,
            'target_amount'   => $targetAmount,
            'percentage'      => $percentage,
            'current_amount'  => $currentAmount,
            'left_to_save'    => round($leftToSave, $decimalPlaces),
            'save_per_month'  => round($piggyRepos->getSuggestedMonthlyAmount($piggyBank), $decimalPlaces),
            'start_date'      => $startDate,
            'target_date'     => $targetDate,
            'order'           => (int)$piggyBank->order,
            'active'          => true,
            'notes'           => null,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_banks/' . $piggyBank->id,
                ],
            ],
        ];
        /** @var Note $note */
        $note = $piggyBank->notes()->first();
        if (null !== $note) {
            $data['notes'] = $note->text;
        }

        return $data;
    }
}
