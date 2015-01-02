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
        $reminder = Reminder::leftJoin('piggy_banks', 'piggy_banks.id', '=', 'reminders.remindersable_id')->where('piggy_banks.reminder','!=','')->first(
            ['reminders.*']
        );

        $I->wantTo('act on reminder ' . boolstr(is_null($reminder)));
        $I->amOnPage('/reminders/' . $reminder->id . '/act');
        $I->see('Money for Nieuwe spullen');
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
        $reminder = Reminder::leftJoin('piggy_banks', 'piggy_banks.id', '=', 'reminders.remindersable_id')->where('piggy_banks.reminder','!=','')->first(
            ['reminders.*']
        );

        $I->wantTo('see a reminder');
        $I->amOnPage('/reminders/'.$reminder->id);
        $I->see('A reminder about');
        $I->see('your piggy bank labelled "Nieuwe spullen"');
    }

}
