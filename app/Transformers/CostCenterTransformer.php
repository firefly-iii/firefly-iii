<?php
/**
 * CostCenterTransformer.php
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
use FireflyIII\Models\CostCenter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CostCenterTransformer
 */
class CostCenterTransformer extends AbstractTransformer
{
    /** @var CostCenterRepositoryInterface */
    private $repository;

    /**
     * CostCenterTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(CostCenterRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * Convert cost center.
     *
     * @param CostCenter $costCenter
     *
     * @return array
     */
    public function transform(CostCenter $costCenter): array
    {
        $this->repository->setUser($costCenter->user);
        $spent  = [];
        $earned = [];
        $start  = $this->parameters->get('start');
        $end    = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $spent  = $this->getSpentInformation($costCenter, $start, $end);
            $earned = $this->getEarnedInformation($costCenter, $start, $end);
        }
        $data = [
            'id'         => (int)$costCenter->id,
            'created_at' => $costCenter->created_at->toAtomString(),
            'updated_at' => $costCenter->updated_at->toAtomString(),
            'name'       => $costCenter->name,
            'spent'      => $spent,
            'earned'     => $earned,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/cost_centers/' . $costCenter->id,
                ],
            ],
        ];

        return $data;
    }

    /**
     * @param CostCenter $costCenter
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    private function getEarnedInformation(CostCenter $costCenter, Carbon $start, Carbon $end): array
    {
        $collection = $this->repository->earnedInPeriodCollection(new Collection([$costCenter]), new Collection, $start, $end);
        $return     = [];
        $total      = [];
        $currencies = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $code = $transaction->transaction_currency_code;
            if (!isset($currencies[$code])) {
                $currencies[$code] = $transaction->transactionCurrency;
            }
            $total[$code] = isset($total[$code]) ? bcadd($total[$code], $transaction->transaction_amount) : $transaction->transaction_amount;
        }
        foreach ($total as $code => $earned) {
            /** @var TransactionCurrency $currency */
            $currency = $currencies[$code];
            $return[] = [
                'currency_id'             => $currency->id,
                'currency_code'           => $code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'amount'                  => round($earned, $currency->decimal_places),
            ];
        }

        return $return;
    }

    /**
     * @param CostCenter $costCenter
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    private function getSpentInformation(CostCenter $costCenter, Carbon $start, Carbon $end): array
    {
        $collection = $this->repository->spentInPeriodCollection(new Collection([$costCenter]), new Collection, $start, $end);
        $return     = [];
        $total      = [];
        $currencies = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $code = $transaction->transaction_currency_code;
            if (!isset($currencies[$code])) {
                $currencies[$code] = $transaction->transactionCurrency;
            }
            $total[$code] = isset($total[$code]) ? bcadd($total[$code], $transaction->transaction_amount) : $transaction->transaction_amount;
        }
        foreach ($total as $code => $spent) {
            /** @var TransactionCurrency $currency */
            $currency = $currencies[$code];
            $return[] = [
                'currency_id'             => $currency->id,
                'currency_code'           => $code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'amount'                  => round($spent, $currency->decimal_places),
            ];
        }

        return $return;
    }

}
