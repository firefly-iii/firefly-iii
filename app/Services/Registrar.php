<?php namespace FireflyIII\Services;

use FireflyIII\User;
use Illuminate\Contracts\Auth\Registrar as RegistrarContract;
use Validator;

/**
 * Class Registrar
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Services
 */
class Registrar implements RegistrarContract
{

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     *
     * @return User
     */
    public function create(array $data)
    {
        return User::create(
            [
                'email'    => $data['email'],
                'password' => $data['password'],
            ]
        );
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(array $data)
    {
        return Validator::make(
            $data, [
                        'email'    => 'required|email|max:255|unique:users',
                        'password' => 'required|confirmed|min:6',
                    ]
        );
    }

}
