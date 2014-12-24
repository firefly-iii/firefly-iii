<?php

/**
 * Class HelpControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 */
class HelpControllerCest
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
    public function show(FunctionalTester $I)
    {
        $I->wantTo('show help for the index page');
        $I->amOnPage('/help/index');
        $I->canSeeResponseCodeIs(200);
        $I->see('text');

    }

    /**
     * @param FunctionalTester $I
     */
    public function showFromCache(FunctionalTester $I)
    {
        $I->wantTo('show help for the index page from the cache.');
        $I->amOnPage('/help/index');
        $I->amOnPage('/help/index');
        $I->canSeeResponseCodeIs(200);
        $I->see('text');

    }

    /**
     * @param FunctionalTester $I
     */
    public function showHelpInvalidRoute(FunctionalTester $I)
    {
        $I->wantTo('show help for a non-existing route.');
        $I->amOnPage('/help/indexXXXX');
        $I->canSeeResponseCodeIs(200);
        $I->see('There is no help for this route');

    }

    /**
     * @param FunctionalTester $I
     */
    public function showHelpNoHelpFile(FunctionalTester $I)
    {
        $I->wantTo('show help for route that has no help file.');
        $I->amOnPage('/help/help.show');
        $I->canSeeResponseCodeIs(200);
        $I->see('text');

    }

}