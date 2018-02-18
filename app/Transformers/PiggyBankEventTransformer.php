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


use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class PiggyBankEventTransformer
 */
class PiggyBankEventTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['piggy_bank', 'transaction'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

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
     * Include piggy bank into end result.
     *
     * @codeCoverageIgnore
     *
     * @param PiggyBankEvent $event
     *
     * @return Item
     */
    public function includePiggyBank(PiggyBankEvent $event): Item
    {
        return $this->item($event->piggyBank, new PiggyBankTransformer($this->parameters), 'piggy_banks');
    }

    /**
     * Include transaction into end result.
     *
     * @codeCoverageIgnore
     *
     * @param PiggyBankEvent $event
     *
     * @return Item
     */
    public function includeTransaction(PiggyBankEvent $event): Item
    {
        $journal  = $event->transactionJournal;
        $pageSize = intval(app('preferences')->getForUser($journal->user, 'listPageSize', 50)->data);

        // journals always use collector and limited using URL parameters.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($journal->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withCategoryInformation();
        $collector->setAllAssetAccounts();
        $collector->setJournals(new Collection([$journal]));
        if (!is_null($this->parameters->get('start')) && !is_null($this->parameters->get('end'))) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $journals = $collector->getJournals();

        return $this->item($journals->first(), new TransactionTransformer($this->parameters), 'transactions');
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
        $account       = $event->piggyBank->account;
        $currencyId    = intval($account->getMeta('currency_id'));
        $decimalPlaces = 2;
        if ($currencyId > 0) {
            /** @var CurrencyRepositoryInterface $repository */
            $repository = app(CurrencyRepositoryInterface::class);
            $repository->setUser($account->user);
            $currency      = $repository->findNull($currencyId);
            $decimalPlaces = $currency->decimal_places;
        }

        $data = [
            'id'         => (int)$event->id,
            'updated_at' => $event->updated_at->toAtomString(),
            'created_at' => $event->created_at->toAtomString(),
            'amount'     => round($event->amount, $decimalPlaces),
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_bank_events/' . $event->id,
                ],
            ],
        ];

        return $data;
    }

}