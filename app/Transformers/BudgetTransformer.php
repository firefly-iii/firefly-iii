<?php

/**
 * BudgetTransformer.php
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

use FireflyIII\Enums\AutoBudgetType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BudgetTransformer
 */
class BudgetTransformer extends AbstractTransformer
{
    private readonly bool                $convertToPrimary;
    private readonly TransactionCurrency $primaryCurrency;
    private array                        $types;

    /**
     * BudgetTransformer constructor.
     */
    public function __construct()
    {
        $this->parameters       = new ParameterBag();
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
        $this->convertToPrimary = Amount::convertToPrimary();
        $this->types            = [
            AutoBudgetType::AUTO_BUDGET_RESET->value    => 'reset',
            AutoBudgetType::AUTO_BUDGET_ROLLOVER->value => 'rollover',
            AutoBudgetType::AUTO_BUDGET_ADJUSTED->value => 'adjusted',
        ];
    }

    /**
     * Transform a budget.
     */
    public function transform(Budget $budget): array
    {

        // info for auto budget.
        $abType    = null;
        $abAmount  = null;
        $abPrimary = null;
        $abPeriod  = null;

        $currency  = $budget->meta['currency'] ?? null;

        if (null !== $budget->meta['auto_budget']) {
            $abType    = $this->types[$budget->meta['auto_budget']['type']];
            $abAmount  = Steam::bcround($budget->meta['auto_budget']['amount'], $currency->decimal_places);
            $abPrimary = $this->convertToPrimary ? Steam::bcround($budget->meta['auto_budget']['pc_amount'], $this->primaryCurrency->decimal_places) : null;
            $abPeriod  = $budget->meta['auto_budget']['period'];
        }

        return [
            'id'                              => (string)$budget->id,
            'created_at'                      => $budget->created_at->toAtomString(),
            'updated_at'                      => $budget->updated_at->toAtomString(),
            'active'                          => $budget->active,
            'name'                            => $budget->name,
            'order'                           => $budget->order,
            'notes'                           => $budget->meta['notes'],
            'auto_budget_type'                => $abType,
            'auto_budget_period'              => $abPeriod,
            'object_group_id'                 => $budget->meta['object_group_id'],
            'object_group_order'              => $budget->meta['object_group_order'],
            'object_group_title'              => $budget->meta['object_group_title'],

            // new currency settings.
            'object_has_currency_setting'     => null !== $budget->meta['currency'],
            'currency_id'                     => null === $currency ? null : (string)$currency->id,
            'currency_code'                   => $currency?->code,
            'currency_name'                   => $currency?->name,
            'currency_symbol'                 => $currency?->symbol,
            'currency_decimal_places'         => $currency?->decimal_places,

            'primary_currency_id'             => (string)$this->primaryCurrency->id,
            'primary_currency_name'           => $this->primaryCurrency->name,
            'primary_currency_code'           => $this->primaryCurrency->code,
            'primary_currency_symbol'         => $this->primaryCurrency->symbol,
            'primary_currency_decimal_places' => $this->primaryCurrency->decimal_places,

            'auto_budget_amount'              => $abAmount,
            'pc_auto_budget_amount'           => $abPrimary,
            'spent'                           => null === $budget->meta['spent'] ? null : $this->beautify($budget->meta['spent']),
            'pc_spent'                        => null === $budget->meta['pc_spent'] ? null : $this->beautify($budget->meta['pc_spent']),
            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/'.$budget->id,
                ],
            ],
        ];
    }

    private function beautify(array $array): array
    {
        $return = [];
        foreach ($array as $data) {
            $data['sum'] = Steam::bcround($data['sum'], (int)$data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
