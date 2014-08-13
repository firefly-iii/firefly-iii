<?php

namespace Firefly\Storage\Piggybank;


/**
 * Interface LimitRepositoryInterface
 *
 * @package Firefly\Storage\Limit
 */
interface PiggybankRepositoryInterface
{

    /**
     * @param $piggyBankId
     *
     * @return mixed
     */
    public function find($piggyBankId);

    /**
     * @return mixed
     */
    public function count();

    /**
     * @return mixed
     */
    public function countRepeating();

    /**
     * @return mixed
     */
    public function countNonrepeating();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param \Piggybank $piggyBank
     * @param            $amount
     *
     * @return mixed
     */
    public function updateAmount(\Piggybank $piggyBank, $amount);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function update($data);

} 