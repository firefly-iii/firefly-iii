<?php

namespace Firefly\Storage\Piggybank;


/**
 * Interface LimitRepositoryInterface
 *
 * @package Firefly\Storage\Limit
 */
interface PiggybankRepositoryInterface
{

    public function find($piggyBankId);
    public function count();
    public function store($data);
    public function get();

    public function updateAmount(\Piggybank $piggyBank, $amount);
} 