<?php


namespace Firefly\Storage\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct()
    {
    }

    public function register($array)
    {
        $user = new \User;
        $user->email = isset($array['email']) ? $array['email'] : null;
        $user->migrated = 0;
        $user->verification = \Str::random(32);
        $user->password = \Hash::make(\Str::random(12));

        if (!$user->isValid()) {
            \Log::error('Invalid user');
            \Session::flash('error', 'Input invalid, please try again.');
            return false;
        }
        $user->save();
        return $user;
    }

    public function auth($array)
    {
        $user = \User::where('email', $array['email'])->first();
        if (!is_null($user)) {
            if (\Hash::check($array['password'], $user->password)) {
            }
        }
        return false;
    }

    public function findByVerification($verification)
    {
        return \User::where('verification', $verification)->first();
    }

    public function findByReset($reset)
    {
        return \User::where('reset', $reset)->first();
    }

    public function findByEmail($email)
    {
        return \User::where('email', $email)->first();
    }

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