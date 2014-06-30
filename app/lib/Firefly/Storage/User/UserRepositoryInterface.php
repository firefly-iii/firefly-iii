<?php


namespace Firefly\Storage\User;


interface UserRepositoryInterface
{
    public function register();

    public function auth();

    public function findByVerification($verification);
    public function findByReset($reset);

    public function findByEmail($email);


} 