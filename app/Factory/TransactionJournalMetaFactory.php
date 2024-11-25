<?php

/**
 * TransactionJournalMetaFactory.php
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

namespace FireflyIII\Factory;

use Carbon\Carbon;
use FireflyIII\Models\TransactionJournalMeta;

/**
 * Class TransactionJournalMetaFactory
 */
class TransactionJournalMetaFactory
{
    public function updateOrCreate(array $data): ?TransactionJournalMeta
    {
        // app('log')->debug('In updateOrCreate()');
        $value       = $data['data'];

        /** @var null|TransactionJournalMeta $entry */
        $entry       = $data['journal']->transactionJournalMeta()->where('name', $data['name'])->first();
        if (null === $value && null !== $entry) {
            // app('log')->debug('Value is empty, delete meta value.');
            $entry->delete();

            return null;
        }

        if ($data['data'] instanceof Carbon) {
            app('log')->debug('Is a carbon object.');
            $value = $data['data']->toW3cString();
        }
        if ('' === (string)$value) {
            // app('log')->debug('Is an empty string.');
            // don't store blank strings.
            if (null !== $entry) {
                app('log')->debug('Will not store empty strings, delete meta value');
                $entry->delete();
            }

            return null;
        }

        if (null === $entry) {
            // app('log')->debug('Will create new object.');
            app('log')->debug(sprintf('Going to create new meta-data entry to store "%s".', $data['name']));
            $entry       = new TransactionJournalMeta();
            $entry->transactionJournal()->associate($data['journal']);
            $entry->name = $data['name'];
        }
        app('log')->debug('Will update value and return.');
        $entry->data = $value;
        $entry->save();

        return $entry;
    }
}
