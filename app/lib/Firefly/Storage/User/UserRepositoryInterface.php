<?php


namespace Firefly\Storage\User;

/**
 * Interface UserRepositoryInterface
 *
 * @package Firefly\Storage\User
 */
interface UserRepositoryInterface
{
    /**
     * @param $array
     *
     * @return mixed
     */
    public function register($array);

    /**
     * @param $reset
     *
     * @return mixed
     */
    public function findByReset($reset);

    /**
     * @param $email
     *
     * @return mixed
     */
    public function findByEmail($email);

    /**
     * @param \User $user
     * @param       $password
     *
     * @return mixed
     */
    public function updatePassword(\User $user, $password);


} 