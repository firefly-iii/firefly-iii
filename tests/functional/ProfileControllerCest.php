<?php

/**
 * Class ProfileControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 */
class ProfileControllerCest
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
        $I->amLoggedAs(['email' => 'thegrumpydictator@gmail.com', 'password' => 'james']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function changePassword(FunctionalTester $I)
    {
        $I->wantTo('change my password.');
        $I->amOnPage('/profile/change-password');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Change your password');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see my profile options');
        $I->amOnPage('/profile');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Profile');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postChangePassword(FunctionalTester $I)
    {
        $I->wantTo('submit a new password.');
        $I->amOnPage('/profile/change-password');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Change your password');
        $I->submitForm(
            '#change-password', [
                                  'old'  => 'james',
                                  'new1' => 'James',
                                  'new2' => 'James'
                              ]
        );
        $I->see('Password changed!');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postChangePasswordInvalidCurrent(FunctionalTester $I)
    {
        $I->wantTo('submit a new password and enter the wrong current password.');
        $I->amOnPage('/profile/change-password');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Change your password');

        $I->submitForm(
            '#change-password', [
                                  'old'  => 'Blablabla',
                                  'new1' => 'James',
                                  'new2' => 'James'
                              ]
        );
        $I->see('Invalid current password!');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postChangePasswordNoNewPassword(FunctionalTester $I)
    {
        $I->wantTo('submit a new password and forget to fill in a new one.');
        $I->amOnPage('/profile/change-password');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Change your password');

        $I->submitForm(
            '#change-password', [
                                  'old'  => 'james',
                                  'new1' => '',
                                  'new2' => ''
                              ]
        );
        $I->see('Do fill in a password!');

    }

    /**
     * @param FunctionalTester $I
     */
    public function postChangePasswordToSame(FunctionalTester $I)
    {
        $I->wantTo('submit a new password but fill in my old one twice.');
        $I->amOnPage('/profile/change-password');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Change your password');

        $I->submitForm(
            '#change-password', [
                                  'old'  => 'james',
                                  'new1' => 'james',
                                  'new2' => 'james'
                              ]
        );
        $I->see('The idea is to change your password.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postChangePasswordNoMatch(FunctionalTester $I)
    {
        $I->wantTo('submit a new password but make a mistake in filling it in twice.');
        $I->amOnPage('/profile/change-password');
        $I->see('thegrumpydictator@gmail.com');
        $I->see('Change your password');

        $I->submitForm(
            '#change-password', [
                                  'old'  => 'james',
                                  'new1' => 'blabla',
                                  'new2' => 'bla'
                              ]
        );
        $I->see('New passwords do not match!');
    }


}