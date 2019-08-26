<?php
/**
 * RecurrenceUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use FireflyIII\Services\Internal\Support\RecurringTransactionTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;

/**
 * Class RecurrenceUpdateService
 *
 * @codeCoverageIgnore
 */
class RecurrenceUpdateService
{
    use TransactionTypeTrait, RecurringTransactionTrait;

    /** @var User */
    private $user;

    /**
     * Updates a recurrence.
     *
     * @param Recurrence $recurrence
     * @param array      $data
     *
     * @return Recurrence
     * @throws FireflyException
     */
    public function update(Recurrence $recurrence, array $data): Recurrence
    {
        $this->user      = $recurrence->user;
        $transactionType = $recurrence->transactionType;
        if (isset($data['recurrence']['type'])) {
            $transactionType = $this->findTransactionType(ucfirst($data['recurrence']['type']));
        }
        // update basic fields first:
        $recurrence->transaction_type_id = $transactionType->id;
        $recurrence->title               = $data['recurrence']['title'] ?? $recurrence->title;
        $recurrence->description         = $data['recurrence']['description'] ?? $recurrence->description;
        $recurrence->first_date          = $data['recurrence']['first_date'] ?? $recurrence->first_date;
        $recurrence->repeat_until        = $data['recurrence']['repeat_until'] ?? $recurrence->repeat_until;
        $recurrence->repetitions         = $data['recurrence']['repetitions'] ?? $recurrence->repetitions;
        $recurrence->apply_rules         = $data['recurrence']['apply_rules'] ?? $recurrence->apply_rules;
        $recurrence->active              = $data['recurrence']['active'] ?? $recurrence->active;


        if (isset($data['recurrence']['repetition_end'])) {
            if (in_array($data['recurrence']['repetition_end'], ['forever', 'until_date'])) {
                $recurrence->repetitions = 0;
            }
            if (in_array($data['recurrence']['repetition_end'], ['forever', 'times'])) {
                $recurrence->repeat_until = null;
            }
        }
        $recurrence->save();

        // update all meta data:
        //$this->updateMetaData($recurrence, $data);

        // update all repetitions
        if (null !== $data['repetitions']) {
            $this->deleteRepetitions($recurrence);
            $this->createRepetitions($recurrence, $data['repetitions'] ?? []);
        }

        // update all transactions (and associated meta-data);
        if (null !== $data['transactions']) {
            $this->deleteTransactions($recurrence);
            $this->createTransactions($recurrence, $data['transactions'] ?? []);
        }

        return $recurrence;
    }
}
