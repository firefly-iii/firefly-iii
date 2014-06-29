<?php


namespace Firefly\Storage\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct()
    {
    }

    public function register()
    {
        $user = new \User;
        $user->email = \Input::get('email');
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

    public function auth()
    {
        $user = \User::where('email', \Input::get('email'))->first();
        if (!is_null($user)) {
            if (\Hash::check(\Input::get('password'), $user->password)) {
            }
        }
        return false;
    }

    public function findByVerification($verification)
    {
        return \User::where('verification', $verification)->first();
    }

}