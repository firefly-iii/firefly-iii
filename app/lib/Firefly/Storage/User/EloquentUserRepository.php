<?php


namespace Firefly\Storage\User;

/**
 * Class EloquentUserRepository
 *
 * @package Firefly\Storage\User
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param $array
     *
     * @return bool|\User
     */
    public function register($array)
    {
        $user = new \User;
        $user->email = isset($array['email']) ? $array['email'] : null;
        $user->migrated = 0;
        $user->reset = \Str::random(32);
        $user->password = \Hash::make(\Str::random(12));

        if (!$user->save()) {
            \Log::error('Invalid user');
            \Session::flash('error', 'Input invalid, please try again: ' . $user->errors()->first());
            return false;
        }
        $user->save();
        return $user;
    }

    /**
     * @param $array
     *
     * @return bool
     */
    public function auth($array)
    {
        $user = \User::where('email', $array['email'])->first();
        if (!is_null($user)) {
            if (\Hash::check($array['password'], $user->password)) {
            }
        }
        return false;
    }

    /**
     * @param $reset
     *
     * @return mixed
     */
    public function findByReset($reset)
    {
        return \User::where('reset', $reset)->first();
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    public function findByEmail($email)
    {
        return \User::where('email', $email)->first();
    }

    /**
     * @param \User $user
     * @param       $password
     *
     * @return bool
     */
    public function updatePassword(\User $user, $password)
    {
        $password = \Hash::make($password);
        /** @noinspection PhpUndefinedFieldInspection */
        $user->password = $password;
        /** @noinspection PhpUndefinedMethodInspection */
        $user->save();
        return true;
    }

}