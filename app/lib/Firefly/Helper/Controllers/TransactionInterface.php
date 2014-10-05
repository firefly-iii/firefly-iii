<?php

namespace Firefly\Helper\Controllers;
use Illuminate\Support\MessageBag;

/**
 * Interface TransactionInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface TransactionInterface {

    /**
     * Store a full transaction journal and associated stuff
     *
     * @param array $data
     *
     * @return MessageBag|\TransactionJournal
     */
    public function store(array $data);

    /**
     * Returns messages about the validation.
     *
     * @param array $data
     *
     * @return array
     */
    public function validate(array $data);

    /**
     * @param \TransactionJournal $journal
     * @param array               $data
     *
     * @return MessageBag|\TransactionJournal
     */
    public function update(\TransactionJournal $journal, array $data);

    /**
     * Overrule the user used when the class is created.
     *
     * @param \User $user
     *
     * @return mixed
     */
    public function overruleUser(\User $user);

} 