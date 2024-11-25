<?php

/**
 * ReconciliationStoreRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Rules\IsValidAmount;
use FireflyIII\Rules\ValidJournals;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class ReconciliationStoreRequest
 */
class ReconciliationStoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Returns the data required by the controller.
     */
    public function getAll(): array
    {
        $transactions = $this->get('journals');
        if (!is_array($transactions)) {
            $transactions = [];
        }
        $data         = [
            'start'         => $this->getCarbonDate('start'),
            'end'           => $this->getCarbonDate('end'),
            'start_balance' => $this->convertString('startBalance'),
            'end_balance'   => $this->convertString('endBalance'),
            'difference'    => $this->convertString('difference'),
            'journals'      => $transactions,
            'reconcile'     => $this->convertString('reconcile'),
        ];
        app('log')->debug('In ReconciliationStoreRequest::getAll(). Will now return data.');

        return $data;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'start'        => 'required|date',
            'end'          => 'required|date',
            'startBalance' => ['nullable', new IsValidAmount()],
            'endBalance'   => ['nullable', new IsValidAmount()],
            'difference'   => ['required', new IsValidAmount()],
            'journals'     => [new ValidJournals()],
            'reconcile'    => 'required|in:create,nothing',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }
}
