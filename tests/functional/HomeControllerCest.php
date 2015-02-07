<?php

/**
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 * Class HomeControllerCest
 */
class HomeControllerCest
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
    public function flush(FunctionalTester $I)
    {
        $I->wantTo('flush the cache');
        $I->amOnPage('/flush');
        $I->canSeeResponseCodeIs(200);
        $I->see('Firefly');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see the home page of Firefly');
        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(200);
        $I->see('Firefly');
    }

    /**
     * @param FunctionalTester $I
     */
    public function indexWithPrefs(FunctionalTester $I)
    {
        \Preference::whereName('frontPageAccounts')->delete();
        \Preference::create(
            [
                'user_id' => 1,
                'name'    => 'frontPageAccounts',
                'data'    => [1,2]
            ]
        );
        $I->wantTo('see the home page of Firefly using pre-set accounts');
        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(200);
        $I->see('Firefly');
    }

    /**
     * @param FunctionalTester $I
     */
    public function rangeJump(FunctionalTester $I)
    {
        $I->wantTo('switch to another date range');
        $I->amOnPage('/jump/6M');
        $I->canSeeResponseCodeIs(200);

    }

    /**
     * @param FunctionalTester $I
     */
    public function sessionNext(FunctionalTester $I)
    {
        $I->wantTo('jump to the next period');
        $I->amOnPage('/next');
        $I->canSeeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function sessionPrev(FunctionalTester $I)
    {
        $I->wantTo('jump to the previous period');
        $I->amOnPage('/prev');
        $I->canSeeResponseCodeIs(200);
    }
}
