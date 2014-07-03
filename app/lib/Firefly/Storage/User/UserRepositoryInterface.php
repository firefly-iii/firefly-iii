<?php


namespace Firefly\Storage\User;


interface UserRepositoryInterface
{
    public function register($array);

    public function auth($array);

    public function findByVerification($verification);
    public function findByReset($reset);

    public function findByEmail($email);

    public function updatePassword(\User $user,$password);


} 