<?php
/**
 * BudgetFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;

/**
 * Class BudgetFormRequest.
 */
class BudgetFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getBudgetData(): array
    {
        return [
            'name'   => $this->string('name'),
            'active' => $this->boolean('active'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        // fixed
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $nameRule   = 'required|between:1,100|uniqueObjectForUser:budgets,name';
        if (null !== $repository->find(intval($this->get('id')))->id) {
            $nameRule = 'required|between:1,100|uniqueObjectForUser:budgets,name,' . intval($this->get('id'));
        }

        return [
            'name'   => $nameRule,
            'active' => 'numeric|between:0,1',
        ];
    }
}
