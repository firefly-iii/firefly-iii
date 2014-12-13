<?php

/**
 * Class UserControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 */
class UserControllerCest
{
    /**
     * @param FunctionalTester $I
     */
    public function _after(FunctionalTester $I)
    {
    }

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
    }

    /**
     * @param FunctionalTester $I
     */
    public function login(FunctionalTester $I)
    {
        $I->wantTo('login');
        $I->amOnPage('/login');
        $I->see('Sign In');
        $I->submitForm('#login', ['email' => 'functional@example.com', 'password' => 'functional']);
        $I->see('functional@example.com');

    }

    /**
     * @param FunctionalTester $I
     */
    public function loginFails(FunctionalTester $I)
    {
        $I->wantTo('fail the login');
        $I->amOnPage('/login');
        $I->see('Sign In');
        $I->submitForm('#login', ['email' => 'functional@example.com', 'password' => 'wrong']);
        $I->see('No good');

    }

    /**
     * @param FunctionalTester $I
     */
    public function logout(FunctionalTester $I)
    {
        $I->wantTo('logout');
        #$I->amOnPage('/logout');
        #$I->am
    }

    /**
     * @param FunctionalTester $I
     */
    public function postLogin(FunctionalTester $I)
    {
        $I->wantTo('post login');
        $I->amOnRoute('login');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRegister(FunctionalTester $I)
    {
        // @codingStandardsIgnoreStart
        $I->wantTo('post-register a new account');
        $I->amOnPage('/register');
        $token = $I->grabValueFrom('input[name=_token]');
        $I->submitForm('#register', ['email' => 'noreply@gmail.com', '_token' => $token]);
        $I->see('Password sent!');
        $I->seeRecord('users', ['email' => 'noreply@gmail.com']);
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRemindme(FunctionalTester $I)
    {
        $I->wantTo('get a password reminder');
        $I->amOnRoute('remindme');
    }

    /**
     * @param FunctionalTester $I
     */
    public function register(FunctionalTester $I)
    {
        $I->wantTo('register a new account');
        $I->amOnRoute('register');


    }

    /**
     * @param FunctionalTester $I
     */
    public function remindme(FunctionalTester $I)
    {
        $I->wantTo('reminded of my password');
        $I->amOnRoute('remindme');
    }

    /**
     * @param FunctionalTester $I
     */
    public function reset(FunctionalTester $I)
    {
        $I->wantTo('reset my password');
        $I->amOnRoute('reset');
    }

}