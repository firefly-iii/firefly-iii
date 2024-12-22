<?php

/**
 * RecurrenceFactory.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use FireflyIII\Services\Internal\Support\RecurringTransactionTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;
use Illuminate\Support\MessageBag;

/**
 * Class RecurrenceFactory
 */
class RecurrenceFactory
{
    use RecurringTransactionTrait;
    use TransactionTypeTrait;

    private MessageBag $errors;
    private User       $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->errors = new MessageBag();
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function create(array $data): Recurrence
    {
        try {
            $type = $this->findTransactionType(ucfirst($data['recurrence']['type']));
        } catch (FireflyException $e) {
            $message = sprintf('Cannot make a recurring transaction of type "%s"', $data['recurrence']['type']);
            app('log')->error($message);
            app('log')->error($e->getTraceAsString());

            throw new FireflyException($message, 0, $e);
        }
        $firstDate         = null;
        $repeatUntil       = null;
        $repetitions       = 0;
        $title             = null;
        $description       = '';
        $applyRules        = true;
        $active            = true;
        $repeatUntilString = null;
        if (array_key_exists('first_date', $data['recurrence'])) {
            /** @var Carbon $firstDate */
            $firstDate = $data['recurrence']['first_date'];
        }
        if (array_key_exists('nr_of_repetitions', $data['recurrence'])) {
            $repetitions = (int) $data['recurrence']['nr_of_repetitions'];
        }
        if (array_key_exists('repeat_until', $data['recurrence'])) {
            $repeatUntil = $data['recurrence']['repeat_until'];
        }
        if (array_key_exists('title', $data['recurrence'])) {
            $title = $data['recurrence']['title'];
        }
        if (array_key_exists('description', $data['recurrence'])) {
            $description = $data['recurrence']['description'];
        }
        if (array_key_exists('apply_rules', $data['recurrence'])) {
            $applyRules = $data['recurrence']['apply_rules'];
        }
        if (array_key_exists('active', $data['recurrence'])) {
            $active = $data['recurrence']['active'];
        }
        $repeatUntilString = $repeatUntil?->format('Y-m-d');

        $recurrence        = new Recurrence(
            [
                'user_id'             => $this->user->id,
                'user_group_id'       => $this->user->user_group_id,
                'transaction_type_id' => $type->id,
                'title'               => $title,
                'description'         => $description,
                'first_date'          => $firstDate?->format('Y-m-d'),
                'repeat_until'        => $repetitions > 0 ? null : $repeatUntilString,
                'latest_date'         => null,
                'repetitions'         => $repetitions,
                'apply_rules'         => $applyRules,
                'active'              => $active,
            ]
        );
        $recurrence->save();

        if (array_key_exists('notes', $data['recurrence'])) {
            $this->updateNote($recurrence, (string) $data['recurrence']['notes']);
        }

        $this->createRepetitions($recurrence, $data['repetitions'] ?? []);

        try {
            $this->createTransactions($recurrence, $data['transactions'] ?? []);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $recurrence->forceDelete();
            $message = sprintf('Could not create recurring transaction: %s', $e->getMessage());
            $this->errors->add('store', $message);

            throw new FireflyException($message, 0, $e);
        }

        return $recurrence;
    }

    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
