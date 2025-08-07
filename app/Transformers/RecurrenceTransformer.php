<?php

/**
 * RecurrenceTransformer.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use Illuminate\Support\Facades\Log;

/**
 * Class RecurringTransactionTransformer
 */
class RecurrenceTransformer extends AbstractTransformer
{
    /**
     * RecurrenceTransformer constructor.
     */
    public function __construct() {}

    /**
     * Transform the recurring transaction.
     *
     * @throws FireflyException
     */
    public function transform(Recurrence $recurrence): array
    {
        Log::debug('Now in Recurrence::transform()');

        $shortType = (string)config(sprintf('firefly.transactionTypesToShort.%s', $recurrence->transactionType->type));
        $reps      = 0 === (int)$recurrence->repetitions ? null : (int)$recurrence->repetitions;
        Log::debug('Get basic data.');

        // basic data.
        return [
            'id'                => (string)$recurrence->id,
            'created_at'        => $recurrence->created_at->toAtomString(),
            'updated_at'        => $recurrence->updated_at->toAtomString(),
            'type'              => $shortType,
            'title'             => $recurrence->title,
            'description'       => $recurrence->description,
            'first_date'        => $recurrence->first_date->toAtomString(),
            'latest_date'       => $recurrence->latest_date?->toAtomString(),
            'repeat_until'      => $recurrence->repeat_until?->toAtomString(),
            'apply_rules'       => $recurrence->apply_rules,
            'active'            => $recurrence->active,
            'nr_of_repetitions' => $reps,
            'notes'             => $recurrence->meta['notes'],
            'repetitions'       => $recurrence->meta['repetitions'],
            'transactions'      => $recurrence->meta['transactions'],
            'links'             => [
                [
                    'rel' => 'self',
                    'uri' => '/recurring/'.$recurrence->id,
                ],
            ],
        ];
    }
}
