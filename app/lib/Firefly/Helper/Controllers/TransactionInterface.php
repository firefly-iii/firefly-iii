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
     * @return MessageBag
     */
    public function store(array $data);

} 