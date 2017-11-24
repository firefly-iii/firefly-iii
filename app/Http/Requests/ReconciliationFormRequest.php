<?php
/**
 * ReconciliationFormRequest.php
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

/**
 * Class ReconciliationFormRequest.
 */
class ReconciliationFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Returns and validates the data required to update a reconciliation.
     *
     * @return array
     */
    public function getJournalData()
    {
        $data = [
            'tags'     => explode(',', $this->string('tags')),
            'amount'   => $this->string('amount'),
            'category' => $this->string('category'),
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            'amount'   => 'numeric|required',
            'category' => 'between:1,255|nullable',
        ];

        return $rules;
    }

}
