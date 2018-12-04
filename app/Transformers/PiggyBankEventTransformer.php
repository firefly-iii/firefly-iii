<?php
/**
 * PiggyBankEventTransformer.php
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


use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class PiggyBankEventTransformer
 */
class PiggyBankEventTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * PiggyBankEventTransformer constructor.
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
     * Convert piggy bank event.
     *
     * @param PiggyBankEvent $event
     *
     * @return array
     */
    public function transform(PiggyBankEvent $event): array
    {
        $account = $event->piggyBank->account;
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($account->user);

        $currencyId    = (int)$accountRepos->getMetaValue($account, 'currency_id');
        $journal       = $event->transactionJournal;
        $transactionId = null;
        $decimalPlaces = 2;
        if ($currencyId > 0) {
            /** @var CurrencyRepositoryInterface $repository */
            $repository = app(CurrencyRepositoryInterface::class);
            $repository->setUser($account->user);
            $currency = $repository->findNull($currencyId);
            /** @noinspection NullPointerExceptionInspection */
            $decimalPlaces = $currency->decimal_places;
        }
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrencyByUser($account->user);
        }
        if (null !== $journal) {
            $transactionId = $journal->transactions()->first()->id;
        }

        $data = [
            'id'              => (int)$event->id,
            'updated_at'      => $event->updated_at->toAtomString(),
            'created_at'      => $event->created_at->toAtomString(),
            'amount'          => round($event->amount, $decimalPlaces),
            'currency_id'     => $currency->id,
            'currency_code'   => $currency->code,
            'currency_symbol' => $currency->symbol,
            'currency_dp'     => $currency->decimal_places,
            'transaction_id'  => $transactionId,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_bank_events/' . $event->id,
                ],
            ],
        ];

        return $data;
    }

}
