<?php


namespace Firefly\Storage\User;


interface UserRepositoryInterface
{
    public function register();
    public function auth();

    public function findByVerification($verification);


} 