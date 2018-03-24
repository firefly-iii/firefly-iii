<?php
declare(strict_types=1);
/**
 * TransactionJournalMetaFactory.php
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


namespace FireflyIII\Factory;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\TransactionJournalMeta;
use Log;

/**
 * Class TransactionJournalMetaFactory
 */
class TransactionJournalMetaFactory
{
    /**
     * @param array $data
     *
     * @return TransactionJournalMeta|null
     */
    public function updateOrCreate(array $data): ?TransactionJournalMeta
    {
        $value = $data['data'];
        /** @var TransactionJournalMeta $entry */
        $entry = $data['journal']->transactionJournalMeta()->where('name', $data['name'])->first();
        if (is_null($value) && !is_null($entry)) {
            try {
                $entry->delete();
            } catch (Exception $e) { // @codeCoverageIgnore
                Log::error(sprintf('Could not delete transaction journal meta: %s', $e->getMessage())); // @codeCoverageIgnore
            }

            return null;
        }

        if ($data['data'] instanceof Carbon) {
            $value = $data['data']->toW3cString();
        }
        if (strlen(strval($value)) === 0) {
            // don't store blank strings.
            if (!is_null($entry)) {
                try {
                    $entry->delete();
                } catch (Exception $e) { // @codeCoverageIgnore
                    Log::error(sprintf('Could not delete transaction journal meta: %s', $e->getMessage())); // @codeCoverageIgnore
                }
            }

            return null;
        }


        if (null === $entry) {
            $entry = new TransactionJournalMeta();
            $entry->transactionJournal()->associate($data['journal']);
            $entry->name = $data['name'];
        }
        $entry->data = $value;
        $entry->save();

        return $entry;
    }

}
