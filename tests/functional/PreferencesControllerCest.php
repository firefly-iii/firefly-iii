<?php

/**
 * Class PreferencesControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 */
class PreferencesControllerCest
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
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see my current set of preferences');
        $I->amOnPage('/preferences');
        $I->see('Preferences');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postIndex(FunctionalTester $I)
    {
        $I->wantTo('want to update my preferences');
        $I->amOnPage('/preferences');
        $I->see('Preferences');
        $I->submitForm('#preferences', []);
        $I->see('Preferences saved!');
    }
}
