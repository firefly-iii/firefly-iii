<?php
/**
 * Entry.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Entry;

use Steam;

/**
 * To extend the exported object, in case of new features in Firefly III for example,
 * do the following:
 *
 * - Add the field(s) to this class. If you add more than one related field, add a new object.
 * - Make sure the "fromJournal"-routine fills these fields.
 * - Add them to the static function that returns its type (key=value. Remember that the only
 *   valid types can be found in config/csv.php (under "roles").
 *
 * These new entries should be should be strings and numbers as much as possible.
 *
 *
 *
 * Class Entry
 * @SuppressWarnings(PHPMD.LongVariable)
 *
 * @package FireflyIII\Export\Entry
 */
final class Entry
{
    // @formatter:off
    public $journal_id;
    public $date;
    public $description;

    public $currency_code;
    public $amount;

    public $transaction_type;

    public $source_account_id;
    public $source_account_name;

    public $destination_account_id;
    public $destination_account_name;

    public $budget_id;
    public $budget_name;
    public $category_id;
    public $category_name;
    // @formatter:on

    /**
     * Entry constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param $object
     *
     * @return Entry
     */
    public static function fromObject($object): Entry
    {
        $entry                           = new self;
        $entry->journal_id               = $object->transaction_journal_id;
        $entry->description              = Steam::decrypt(intval($object->journal_encrypted), $object->journal_description);
        $entry->amount                   = $object->amount;
        $entry->date                     = $object->date;
        $entry->transaction_type         = $object->transaction_type;
        $entry->currency_code            = $object->transaction_currency_code;
        $entry->source_account_id        = $object->account_id;
        $entry->source_account_name      = Steam::decrypt(intval($object->account_name_encrypted), $object->account_name);
        $entry->destination_account_id   = $object->opposing_account_id;
        $entry->destination_account_name = Steam::decrypt(intval($object->opposing_account_encrypted), $object->opposing_account_name);
        $entry->category_id              = $object->category_id ?? '';
        $entry->category_name            = $object->category_name ?? '';
        $entry->budget_id                = $object->budget_id ?? '';
        $entry->budget_name              = $object->budget_name ?? '';

        // update description when transaction description is different:
        if (!is_null($object->description) && $object->description != $entry->description) {
            $entry->description = $entry->description . ' (' . $object->description . ')';
        }

        return $entry;
    }


}
