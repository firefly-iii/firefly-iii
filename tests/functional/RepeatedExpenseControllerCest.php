<?php
use Carbon\Carbon;
use FireflyIII\Models\PiggyBank;
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
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('delete a repeated expense');
        $I->amOnPage('/repeatedexpenses/delete/' . $repeatedExpense->id);
        $I->see('Delete "' . $repeatedExpense->name . '"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('destroy a repeated expense');
        $I->amOnPage('/repeatedexpenses/delete/' . $repeatedExpense->id);
        $I->submitForm('#destroy', []);
        $I->see('Repeated expense &quot;' . $repeatedExpense->name . '&quot; deleted.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('edit a repeated expense');
        $I->amOnPage('/repeatedexpenses/edit/' . $repeatedExpense->id);
        $I->see('Edit repeated expense "' . $repeatedExpense->name . '"');

    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('see all repeated expenses');
        $I->amOnPage('/repeatedexpenses');
        $I->see('Overview');
        $I->see($repeatedExpense->name);
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('view a repeated expense');
        $I->amOnPage('/repeatedexpenses/show/' . $repeatedExpense->id);
        $I->see($repeatedExpense->name);
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
    public function storeValidate(FunctionalTester $I)
    {
        $I->wantTo('validate a repeated expense');
        $I->amOnPage('/repeatedexpenses/create');
        $I->submitForm(
            '#store', [
                        'name'               => 'TestRepeatedExpenseXX',
                        'account_id'         => 1,
                        'targetamount'       => 1000,
                        'targetdate'         => Carbon::now()->format('Y-m-d'),
                        'rep_length'         => 'month',
                        'rep_every'          => 0,
                        'rep_times'          => 0,
                        'remind_me'          => 1,
                        'reminder'           => 'month',
                        'post_submit_action' => 'validate_only',
                    ]
        );

        $I->see('TestRepeatedExpenseXX');
        $I->see('OK');
        $I->seeInSession('successes');
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeAndReturn(FunctionalTester $I)
    {
        $I->wantTo('store a repeated expense and return');
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
                        'post_submit_action' => 'create_another',
                    ]
        );

        $I->see('Piggy bank &quot;TestRepeatedExpense&quot; stored.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeFail(FunctionalTester $I)
    {
        $I->wantTo('store a repeated expense and fail');
        $I->amOnPage('/repeatedexpenses/create');
        $I->submitForm(
            '#store', [
                        'name'               => '',
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

        $I->see('Could not store repeated expense: The name field is required.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('update a repeated expense');
        $I->amOnPage('/repeatedexpenses/edit/' . $repeatedExpense->id);
        $I->submitForm(
            '#update', [
                         'name'               => $repeatedExpense->name . '!',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => $repeatedExpense->targetdate->format('Y-m-d'),
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'update',
                     ]
        );
        $I->see('Repeated expense &quot;' . $repeatedExpense->name . '!&quot; updated.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateValidate(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('validate an updated repeated expense');
        $I->amOnPage('/repeatedexpenses/edit/' . $repeatedExpense->id);
        $I->submitForm(
            '#update', [
                         'name'               => $repeatedExpense->name . 'ABCD',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => $repeatedExpense->targetdate->format('Y-m-d'),
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'validate_only',
                     ]
        );
        $I->see($repeatedExpense->name . 'ABCD');
        $I->see('OK');
        $I->seeInSession('successes');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturnToEdit(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('update a repeated expense and return to edit screen');
        $I->amOnPage('/repeatedexpenses/edit/' . $repeatedExpense->id);
        $I->submitForm(
            '#update', [
                         'name'               => $repeatedExpense->name . '!',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => $repeatedExpense->targetdate->format('Y-m-d'),
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'return_to_edit',
                     ]
        );
        $I->see('Repeated expense &quot;' . $repeatedExpense->name . '!&quot; updated.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateFail(FunctionalTester $I)
    {
        $repeatedExpense = PiggyBank::where('repeats', 1)->first();
        $I->wantTo('try to update a repeated expense and fail');
        $I->amOnPage('/repeatedexpenses/edit/' . $repeatedExpense->id);
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
