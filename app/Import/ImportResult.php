<?php
/**
 * ImportResult.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

/**
 * Class ImportResult
 *
 * @package FireflyIII\Import
 */
class ImportResult
{
    const IMPORT_SUCCESS = 1;
    const IMPORT_FAILED  = 0;
    const IMPORT_VALID   = 2;

    /** @var Collection */
    public $errors;
    /** @var  TransactionJournal */
    public $journal;
    /** @var  Collection */
    public $messages;
    /** @var int */
    public $status = 0;
    /** @var string */
    public $title = 'No result yet.';
    /** @var  Collection */
    public $warnings;

    /**
     * ImportResult constructor.
     */
    public function __construct()
    {
        $this->errors   = new Collection;
        $this->warnings = new Collection;
        $this->messages = new Collection;
    }

    /**
     * @param string $error
     *
     * @return $this
     */
    public function appendError(string $error)
    {
        $this->errors->push($error);

        return $this;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function appendMessage(string $message)
    {
        $this->messages->push($message);

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function appendTitle(string $title)
    {
        $this->title .= $title;

        return $this;
    }

    /**
     * @param string $warning
     *
     * @return $this
     */
    public function appendWarning(string $warning)
    {
        $this->warnings->push($warning);

        return $this;
    }

    /**
     * @return $this
     */
    public function failed()
    {
        $this->status = self::IMPORT_FAILED;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === self::IMPORT_SUCCESS;
    }

    /**
     * @param Collection $errors
     */
    public function setErrors(Collection $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @param TransactionJournal $journal
     */
    public function setJournal(TransactionJournal $journal)
    {
        $this->journal = $journal;
    }

    /**
     * @param Collection $messages
     */
    public function setMessages(Collection $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param Collection $warnings
     */
    public function setWarnings(Collection $warnings)
    {
        $this->warnings = $warnings;
    }

    /**
     * @return $this
     */
    public function success()
    {
        $this->status = self::IMPORT_SUCCESS;

        return $this;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->status === self::IMPORT_VALID;
    }

    /**
     *
     */
    public function validated()
    {
        $this->status = self::IMPORT_VALID;
    }


}
