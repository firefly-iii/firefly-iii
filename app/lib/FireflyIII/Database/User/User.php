<?php

namespace FireflyIII\Database\User;


/**
 * Class User
 *
 * @package FireflyIII\Database
 */
class User
{

    /**
     * @param $mail
     *
     * @return null|\User
     */
    public function findByEmail($mail)
    {
        return \User::where('email', $mail)->first();
    }

    /**
     * @param $reset
     *
     * @return null|User
     */
    public function findByReset($reset)
    {
        return \User::where('reset', $reset)->first();
    }

    /**
     * @param array $data
     *
     * @return bool|\User
     */
    public function register(array $data)
    {
        $user           = new \User;
        $user->email    = isset($data['email']) ? $data['email'] : null;
        $user->reset    = \Str::random(32);
        $user->password = \Hash::make(\Str::random(12));

        // validate user:
        if (!$user->isValid()) {
            \Log::error('Invalid user with data: ' . isset($data['email']) ? $data['email'] : '(no email!)');
            \Session::flash('error', 'Input invalid, please try again: ' . $user->getErrors()->first());

            return false;
        }
        $user->save();

        return $user;

    }

    /**
     * @param \User $user
     * @param       $password
     *
     * @return bool
     */
    public function updatePassword(\User $user, $password)
    {
        $user->password = $password;
        $user->forceSave();

        return true;
    }

} 