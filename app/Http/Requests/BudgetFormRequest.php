<?php
/**
 * BudgetFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;

/**
 * Class BudgetFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
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
            'name'   => trim($this->input('name')),
            'active' => intval($this->input('active')) == 1,
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $nameRule   = 'required|between:1,100|uniqueObjectForUser:budgets,name';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $nameRule = 'required|between:1,100|uniqueObjectForUser:budgets,name,' . intval($this->get('id'));
        }

        return [
            'name'   => $nameRule,
            'active' => 'numeric|between:0,1',
        ];
    }
}
