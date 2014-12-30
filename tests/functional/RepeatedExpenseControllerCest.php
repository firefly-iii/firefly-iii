<?php
use Carbon\Carbon;

/**
 * Class RepeatedExpenseControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 */
class RepeatedExpenseControllerCest
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
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a repeated expense');
        $I->amOnPage('/repeatedexpenses/create');
        $I->see('Create new repeated expense');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a repeated expense');
        $I->amOnPage('/repeatedexpenses/delete/4');
        $I->see('Delete "Nieuwe kleding"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a repeated expense');
        $I->amOnPage('/repeatedexpenses/delete/4');
        $I->submitForm('#destroy', []);
        $I->dontSeeInDatabase('piggy_banks', ['id' => 5]);
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a repeated expense');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->see('Edit repeated expense "Nieuwe kleding"');

    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all repeated expenses');
        $I->amOnPage('/repeatedexpenses');
        $I->see('Overview');
        $I->see('Nieuwe kleding');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('view a repeated expense');
        $I->amOnPage('/repeatedexpenses/show/4');
        $I->see('Nieuwe kleding');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a repeated expense');
        $I->amOnPage('/repeatedexpenses/create');
        $I->submitForm(
            '#store', [
                        'name'               => 'TestRepeatedExpense',
                        'account_id'         => 1,
                        'targetamount'       => 1000,
                        'targetdate'         => Carbon::now()->format('Y-m-d'),
                        'rep_length'         => 'month',
                        'rep_every'          => 0,
                        'rep_times'          => 0,
                        'remind_me'          => 1,
                        'reminder'           => 'month',
                        'post_submit_action' => 'store',
                    ]
        );

        $I->see('Piggy bank &quot;TestRepeatedExpense&quot; stored.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a repeated expense');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->submitForm(
            '#update', [
                         'name'               => 'Nieuwe kleding!',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => '2014-12-30',
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'update',
                     ]
        );
        $I->see('Repeated expense &quot;Nieuwe kleding!&quot; updated.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturnToEdit(FunctionalTester $I)
    {
        $I->wantTo('update a repeated expense and return to edit screen');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->submitForm(
            '#update', [
                         'name'               => 'Nieuwe kleding!',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => '2014-12-30',
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'return_to_edit',
                     ]
        );
        $I->see('Repeated expense &quot;Nieuwe kleding!&quot; updated.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateFail(FunctionalTester $I)
    {
        $I->wantTo('try to update a repeated expense and fail');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->submitForm(
            '#update', [
                         'name'               => '',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => '2014-12-30',
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'update',
                     ]
        );
        $I->see('The name field is required.');
    }
}