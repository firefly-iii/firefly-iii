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
        $I->amLoggedAs(['email' => 'thegrumpydictator@gmail.com', 'password' => 'james']);
        $I->wantTo('logout');
        $I->amOnPage('/');
        $I->click('Logout');
        $I->see('Firefly III &mdash; Sign In');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRegister(FunctionalTester $I)
    {
        $I->wantTo('post-register a new account');
        $I->amOnPage('/register');
        $I->submitForm('#register', ['email' => 'noreply@gmail.com']);
        $I->see('You\'re about to get an e-mail. Please follow its instructions.');
        $I->seeRecord('users', ['email' => 'noreply@gmail.com']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRegisterFail(FunctionalTester $I)
    {
        $I->wantTo('post-register a new account and fail');
        $I->amOnPage('/register');
        $I->submitForm('#register', ['email' => 'XXxxxxx']);
        $I->see('Input invalid, please try again: The email must be a valid email address.');
        $I->dontseeRecord('users', ['email' => 'XXxxxxx']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRemindme(FunctionalTester $I)
    {
        $I->wantTo('get a password reminder');
        $I->amOnRoute('remindMe');
        $I->submitForm('#remindMe', ['email' => 'functional@example.com']);
        $I->see('You\'re about to get an e-mail.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRemindmeFail(FunctionalTester $I)
    {
        $I->wantTo('get a password reminder and fail');
        $I->amOnRoute('remindMe');
        $I->submitForm('#remindMe', ['email' => 'abcdee']);
        $I->see('No good!');
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
    public function remindMe(FunctionalTester $I)
    {
        $I->wantTo('reminded of my password');
        $I->amOnRoute('remindMe');
        $I->see('Firefly III &mdash; Reset your password');
    }

    /**
     * @param FunctionalTester $I
     */
    public function resetFail(FunctionalTester $I)
    {
        $I->wantTo('reset my password and fail');
        $I->amOnPage('/reset/123');
        $I->see('No reset code found!');
    }

    /**
     * @param FunctionalTester $I
     */
    public function reset(FunctionalTester $I)
    {
        $I->wantTo('reset my password');
        $I->amOnPage('/reset/okokokokokokokokokokokokokokokok');
        $I->see('You\'re about to get an e-mail.');
    }

}
