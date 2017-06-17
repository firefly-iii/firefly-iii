<?php
/**
 * ImportStorage.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Storage;

use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Import\Object\ImportObject;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Support\Collection;
use Log;

/**
 * Is capable of storing individual ImportJournal objects.
 * Class ImportStorage
 *
 * @package FireflyIII\Import\Storage
 */
class ImportStorage
{
    /** @var string */
    private $dateFormat = 'Ymd';
    /** @var Collection */
    private $objects;

    /**
     * ImportStorage constructor.
     */
    public function __construct()
    {
        $this->objects = new Collection;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat(string $dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }


    /**
     * @param Collection $objects
     */
    public function setObjects(Collection $objects)
    {
        $this->objects = $objects;
    }


    /**
     * Do storage of import objects
     */
    public function store()
    {
        /**
         * @var int          $index
         * @var ImportJournal $object
         */
        foreach ($this->objects as $index => $object) {
            Log::debug(sprintf('Going to store object #%d', $index));

            die('Cannot actually store yet.');
        }
    }

}