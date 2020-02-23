<?php
/**
 * AvailableBudgetTransformer.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;


use FireflyIII\Models\AvailableBudget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AvailableBudgetTransformer
 */
class AvailableBudgetTransformer extends AbstractTransformer
{
    /** @var NoBudgetRepositoryInterface */
    private $noBudgetRepository;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * CurrencyTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository         = app(BudgetRepositoryInterface::class);
        $this->opsRepository      = app(OperationsRepositoryInterface::class);
        $this->noBudgetRepository = app(NoBudgetRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Transform the note.
     *
     * @param AvailableBudget $availableBudget
     *
     * @return array
     */
    public function transform(AvailableBudget $availableBudget): array
    {
        $this->repository->setUser($availableBudget->user);

        $currency = $availableBudget->transactionCurrency;
        $data     = [
            'id'                      => (int)$availableBudget->id,
            'created_at'              => $availableBudget->created_at->toAtomString(),
            'updated_at'              => $availableBudget->updated_at->toAtomString(),
            'currency_id'             => $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,
            'amount'                  => round($availableBudget->amount, $currency->decimal_places),
            'start'                   => $availableBudget->start_date->format('Y-m-d'),
            'end'                     => $availableBudget->end_date->format('Y-m-d'),
            'spent_in_budgets'        => [],
            'spent_no_budget'         => [],
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/available_budgets/' . $availableBudget->id,
                ],
            ],
        ];
        $start    = $this->parameters->get('start');
        $end      = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $data['spent_in_budgets'] = $this->getSpentInBudgets();
            $data['spent_no_budget']  = $this->spentOutsideBudgets();
        }

        return $data;
    }

    /**
     * @return array
     */
    private function getSpentInBudgets(): array
    {
        $allActive = $this->repository->getActiveBudgets();

        return $this->opsRepository->spentInPeriodMc(
            $allActive, new Collection, $this->parameters->get('start'), $this->parameters->get('end')
        );

    }

    /**
     * @return array
     */
    private function spentOutsideBudgets(): array
    {
        return $this->noBudgetRepository->spentInPeriodWoBudgetMc(new Collection, $this->parameters->get('start'), $this->parameters->get('end'));
    }

}
