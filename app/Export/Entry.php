<?php
/**
 * Entry.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export;

use FireflyIII\Models\TransactionJournal;

/**
 * To extend the exported object, in case of new features in Firefly III for example,
 * do the following:
 *
 * - Add the field(s) to this class
 * - Make sure the "fromJournal"-routine fills these fields.
 * - Add them to the static function that returns its type (key=value. Remember that the only
 *   valid types can be found in config/csv.php (under "roles").
 *
 * These new entries should be should be strings and numbers as much as possible.
 *
 *
 *
 * Class Entry
 *
 * @package FireflyIII\Export
 */
class Entry
{
    /** @var  string */
    public $amount;
    /** @var  string */
    public $date;
    /** @var  string */
    public $description;

    /**
     * @param TransactionJournal $journal
     *
     * @return Entry
     */
    public static function fromJournal(TransactionJournal $journal)
    {

        $entry = new self;
        $entry->setDescription($journal->description);
        $entry->setDate($journal->date->format('Y-m-d'));
        $entry->setAmount($journal->amount);

        return $entry;

    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        // key = field name (see top of class)
        // value = field type (see csv.php under 'roles')
        return [
            'amount'      => 'amount',
            'date'        => 'date-transaction',
            'description' => 'description',
        ];
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }


}