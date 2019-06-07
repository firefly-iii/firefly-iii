<?php
/**
 * CategoryTransformer.php
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
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CategoryTransformer
 */
class CategoryTransformer extends AbstractTransformer
{
    /** @var CategoryRepositoryInterface */
    private $repository;

    /**
     * CategoryTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(CategoryRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Convert category.
     *
     * @param Category $category
     *
     * @return array
     */
    public function transform(Category $category): array
    {
        $this->repository->setUser($category->user);
        $spent  = [];
        $earned = [];
        $start  = $this->parameters->get('start');
        $end    = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $spent  = $this->getSpentInformation($category, $start, $end);
            $earned = $this->getEarnedInformation($category, $start, $end);
        }
        $data = [
            'id'         => (int)$category->id,
            'created_at' => $category->created_at->toAtomString(),
            'updated_at' => $category->updated_at->toAtomString(),
            'name'       => $category->name,
            'spent'      => $spent,
            'earned'     => $earned,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/categories/' . $category->id,
                ],
            ],
        ];

        return $data;
    }

    /**
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    private function getEarnedInformation(Category $category, Carbon $start, Carbon $end): array
    {
        $collection = $this->repository->earnedInPeriodCollection(new Collection([$category]), new Collection, $start, $end);
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
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    private function getSpentInformation(Category $category, Carbon $start, Carbon $end): array
    {
        $collection = $this->repository->spentInPeriodCollection(new Collection([$category]), new Collection, $start, $end);
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
