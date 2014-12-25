<?php

/**
 * Class RecurringControllerCest
 */
class RecurringControllerCest
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
        $I->wantTo('create a recurring transaction');
        $I->amOnPage('/recurring/create');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a recurring transaction');
        $I->amOnPage('/recurring/delete/1');
        $I->see('Delete "Huur"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a recurring transaction');
        $I->amOnPage('/recurring/delete/1');
        $I->see('Delete "Huur"');
        $I->submitForm('#destroy', []);
        $I->see('The recurring transaction was deleted.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a recurring transaction');
        $I->amOnPage('/recurring/edit/1');


    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all recurring transactions');
        $I->amOnPage('/recurring');
    }

    /**
     * @param FunctionalTester $I
     */
    public function rescan(FunctionalTester $I)
    {
        $I->wantTo('rescan a recurring transaction');
        $I->amOnPage('/recurring/rescan/1');
        $I->see('Rescanned everything.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function rescanInactive(FunctionalTester $I)
    {
        $I->wantTo('rescan an inactive recurring transaction');
        $I->amOnPage('/recurring/rescan/2');
        $I->see('Inactive recurring transactions cannot be scanned.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('show a recurring transaction');
        $I->amOnPage('/recurring/show/1');
        $I->see('Huur');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a recurring transaction');
        $I->amOnPage('/recurring/create');
        $I->submitForm(
            '#store', [
                        'name'               => 'Some recurring',
                        'match'              => 'one,two',
                        'amount_min'         => 10,
                        'amount_max'         => 20,
                        'post_submit_action' => 'store',
                        'date'               => date('Y-m-d'),
                        'repeat_freq'        => 'monthly',
                        'skip'               => 0
                    ]
        );
        $I->see('Recurring transaction &quot;Some recurring&quot; stored.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeFail(FunctionalTester $I)
    {
        $I->wantTo('store a recurring transaction and fail');
        $I->amOnPage('/recurring/create');
        $I->submitForm(
            '#store', [
                        'name'        => 'Some recurring',
                        'match'       => '',
                        'amount_min'  => 10,
                        'amount_max'  => 20,
                        'date'        => date('Y-m-d'),
                        'repeat_freq' => 'monthly',
                        'skip'        => 0
                    ]
        );
        $I->dontSeeInDatabase('recurring_transactions', ['name' => 'Some recurring']);
        $I->see('Could not store recurring transaction');
    }

    public function storeRecreate(FunctionalTester $I)
    {
        $I->wantTo('validate a recurring transaction and create another one');
        $I->amOnPage('/recurring/create');
        $I->submitForm(
            '#store', [
                        'name'               => 'Some recurring',
                        'match'              => 'one,two',
                        'amount_min'         => 10,
                        'amount_max'         => 20,
                        'post_submit_action' => 'create_another',
                        'date'               => date('Y-m-d'),
                        'repeat_freq'        => 'monthly',
                        'skip'               => 0,

                    ]
        );
        $I->see('Recurring transaction &quot;Some recurring&quot; stored.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeValidate(FunctionalTester $I)
    {
        $I->wantTo('validate a recurring transaction');
        $I->amOnPage('/recurring/create');
        $I->submitForm(
            '#store', [
                        'name'               => 'Some recurring',
                        'match'              => 'one,two',
                        'amount_min'         => 10,
                        'amount_max'         => 20,
                        'post_submit_action' => 'validate_only',
                        'date'               => date('Y-m-d'),
                        'repeat_freq'        => 'monthly',
                        'skip'               => 0,

                    ]
        );
        $I->see('form-group has-success has-feedback');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a recurring transaction');
        $I->amOnPage('/recurring/edit/1');
        $I->submitForm(
            '#update', [
                         'name'        => 'Some recurring',
                         'match'       => 'bla,bla',
                         'amount_min'  => 10,
                         'amount_max'  => 20,
                         'date'        => date('Y-m-d'),
                         'repeat_freq' => 'monthly',
                         'skip'        => 0
                     ]
        );
        $I->see('Recurring transaction &quot;Some recurring&quot; updated.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateReturn(FunctionalTester $I)
    {
        $I->wantTo('update a recurring transaction and return to edit it');
        $I->amOnPage('/recurring/edit/1');
        $I->submitForm(
            '#update', [
                         'name'        => 'Some recurring',
                         'match'       => 'bla,bla',
                         'amount_min'  => 10,
                         'amount_max'  => 20,
                         'post_submit_action' => 'return_to_edit',
                         'date'        => date('Y-m-d'),
                         'repeat_freq' => 'monthly',
                         'skip'        => 0
                     ]
        );
        $I->see('Recurring transaction &quot;Some recurring&quot; updated.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateFail(FunctionalTester $I)
    {
        $I->wantTo('update a recurring transaction and fail');
        $I->amOnPage('/recurring/edit/1');
        $I->submitForm(
            '#update', [
                         'name'        => 'Some recurring',
                         'match'       => '',
                         'amount_min'  => 10,
                         'amount_max'  => 20,
                         'date'        => date('Y-m-d'),
                         'repeat_freq' => 'monthly',
                         'skip'        => 0
                     ]
        );
        $I->see('Could not update recurring transaction');
    }

}