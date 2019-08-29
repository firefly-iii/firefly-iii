<?php
/**
 * RecurrenceFactory.php
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
/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Factory;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use FireflyIII\Services\Internal\Support\RecurringTransactionTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class RecurrenceFactory
 */
class RecurrenceFactory
{
    /** @var User */
    private $user;

    /** @var MessageBag */
    private $errors;

    use TransactionTypeTrait, RecurringTransactionTrait;

    /**
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
        $this->errors = new MessageBag;
    }

    /**
     * @param array $data
     *
     * @return Recurrence
     * @throws FireflyException
     */
    public function create(array $data): Recurrence
    {
        try {
            $type = $this->findTransactionType(ucfirst($data['recurrence']['type']));
        } catch (FireflyException $e) {
            $message = sprintf('Cannot make a recurring transaction of type "%s"', $data['recurrence']['type']);
            Log::error($message);
            Log::error($e->getTraceAsString());

            throw new FireflyException($message);
        }
        /** @var Carbon $firstDate */
        $firstDate = $data['recurrence']['first_date'];

        $repetitions = (int)$data['recurrence']['repetitions'];
        $recurrence  = new Recurrence(
            [
                'user_id'             => $this->user->id,
                'transaction_type_id' => $type->id,
                'title'               => $data['recurrence']['title'],
                'description'         => $data['recurrence']['description'],
                'first_date'          => $firstDate->format('Y-m-d'),
                'repeat_until'        => $repetitions > 0 ? null : $data['recurrence']['repeat_until'],
                'latest_date'         => null,
                'repetitions'         => $data['recurrence']['repetitions'],
                'apply_rules'         => $data['recurrence']['apply_rules'],
                'active'              => $data['recurrence']['active'],
            ]
        );
        $recurrence->save();

        $this->createRepetitions($recurrence, $data['repetitions'] ?? []);
        try {
            $this->createTransactions($recurrence, $data['transactions'] ?? []);
            // @codeCoverageIgnoreStart
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            $recurrence->forceDelete();
            $message = sprintf('Could not create recurring transaction: %s', $e->getMessage());
            $this->errors->add('store', $message);
            throw new FireflyException($message);

        }

        // @codeCoverageIgnoreEnd

        return $recurrence;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
