<?php

/**
 * Class ReminderControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 */
class ReminderControllerCest
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
    public function act(FunctionalTester $I)
    {
        $I->wantTo('act on a reminder');
        $I->amOnPage('/reminders/1/act');
        $I->see('Money for Weekly reminder for clothes');
    }

    /**
     * @param FunctionalTester $I
     */
    public function dismiss(FunctionalTester $I)
    {
        $I->wantTo('dismiss a reminder');
        $I->amOnPage('/reminders/1/dismiss');
        $I->see('Reminder dismissed');
    }

    /**
     * @param FunctionalTester $I
     */
    public function notNow(FunctionalTester $I)
    {
        $I->wantTo('ignore a reminder');
        $I->amOnPage('/reminders/1/notNow');
        $I->see('Reminder dismissed');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('see a reminder');
        $I->amOnPage('/reminders/1');
        $I->see('A reminder about');
        $I->see('your piggy bank labelled "Weekly reminder for clothes"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function actOnInvalid(FunctionalTester $I)
    {
        $I->wantTo('act on an invalid reminder');
        $I->amOnPage('/reminders/2/act');
        $I->see('This reminder has an invalid class connected to it.');
    }

}