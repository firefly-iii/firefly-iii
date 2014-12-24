<?php

/**
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class PiggyBankControllerCest
 */
class PiggyBankControllerCest
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
    public function add(FunctionalTester $I)
    {
        $I->wantTo('add money to a piggy bank');
        $I->amOnPage('/piggy_banks/add/1');
        $I->see('Add money to New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a piggy bank');
        $I->amOnPage('/piggy_banks/create');
        $I->see('Create new piggy bank');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a piggy bank');
        $I->amOnPage('/piggy_banks/delete/1');
        $I->see('Delete &quot;New camera&quot;');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a piggy bank');
        $I->amOnPage('/piggy_banks/delete/1');
        $I->see('Delete &quot;New camera&quot;');
        $I->submitForm('#destroy', []);
        $I->see('Piggy bank &quot;New camera&quot; deleted.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a piggy bank');
        $I->amOnPage('/piggy_banks/edit/1');
        $I->see('Edit piggy bank "New camera"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function editWithTargetDate(FunctionalTester $I)
    {
        $I->wantTo('edit a piggy bank with a target date');
        $I->amOnPage('/piggy_banks/edit/2');
        $I->see('Edit piggy bank "New clothes"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('view all piggy banks');
        $I->amOnPage('/piggy_banks');
        $I->see('Piggy banks');
        $I->see('New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postAdd(FunctionalTester $I)
    {
        $I->wantTo('process adding money to a piggy bank');
        $I->amOnPage('/piggy_banks/add/1');
        $I->see('Add money to New camera');
        $I->submitForm('#add', ['amount' => 100]);
        $I->see(',00 to &quot;New camera&quot;.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postAddTooMuch(FunctionalTester $I)
    {
        $I->wantTo('try to add too much money to a piggy bank');
        $I->amOnPage('/piggy_banks/add/1');
        $I->see('Add money to New camera');
        $I->submitForm('#add', ['amount' => 100000]);
        $I->see(',00 to &quot;New camera&quot;.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRemove(FunctionalTester $I)
    {
        $I->wantTo('process removing money from a piggy bank');
        $I->amOnPage('/piggy_banks/add/1');
        $I->see('Add money to New camera');
        $I->submitForm('#add', ['amount' => 100]);
        $I->see(',00 to &quot;New camera&quot;.');
        $I->amOnPage('/piggy_banks/remove/1');
        $I->see('Remove money from New camera');
        $I->submitForm('#remove', ['amount' => 50]);
        $I->see(',00 from &quot;New camera&quot;.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRemoveFail(FunctionalTester $I)
    {
        $I->wantTo('process removing too much money from a piggy bank');
        $I->amOnPage('/piggy_banks/add/1');
        $I->see('Add money to New camera');
        $I->submitForm('#add', ['amount' => 100]);
        $I->see(',00 to &quot;New camera&quot;.');
        $I->amOnPage('/piggy_banks/remove/1');
        $I->see('Remove money from New camera');
        $I->submitForm('#remove', ['amount' => 500]);
        $I->see(',00 from &quot;New camera&quot;.');
    }


    /**
     * @param FunctionalTester $I
     */
    public function remove(FunctionalTester $I)
    {
        $I->wantTo('removing money from a piggy bank');
        $I->amOnPage('/piggy_banks/remove/1');
        $I->see('Remove money from New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('view a piggy bank');
        $I->amOnPage('/piggy_banks/show/1');
        $I->see('New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a new piggy bank');
        $I->amOnPage('/piggy_banks/create');
        $I->see('Create new piggy bank');
        $I->submitForm(
            '#store', ['name'          => 'Some new piggy bank',
                       'rep_every'     => 0,
                       'reminder_skip' => 0,
                       'remind_me'     => 0,
                       'order'         => 3,
                       'account_id'    => 1, 'targetamount' => 1000]
        );
        $I->see('Piggy bank &quot;Some new piggy bank&quot; stored.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeAndReturn(FunctionalTester $I)
    {
        $I->wantTo('store a new piggy bank and return');
        $I->amOnPage('/piggy_banks/create');
        $I->see('Create new piggy bank');
        $I->submitForm(
            '#store', ['name'               => 'Some new piggy bank',
                       'rep_every'          => 0,
                       'reminder_skip'      => 0,
                       'post_submit_action' => 'create_another',
                       'remind_me'          => 0,
                       'order'              => 3,
                       'account_id'         => 1, 'targetamount' => 1000]
        );
        $I->see('Piggy bank &quot;Some new piggy bank&quot; stored.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeFail(FunctionalTester $I)
    {
        $I->wantTo('fail storing a new piggy bank');
        $I->amOnPage('/piggy_banks/create');
        $I->see('Create new piggy bank');
        $I->submitForm(
            '#store', ['name'          => null,
                       'rep_every'     => 0,
                       'reminder_skip' => 0,
                       'remind_me'     => 0,
                       'order'         => 3,
                       'account_id'    => 1, 'targetamount' => 1000]
        );
        $I->see('Name is too short');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a piggy bank');
        $I->amOnPage('/piggy_banks/edit/1');
        $I->see('Edit piggy bank "New camera"');
        $I->submitForm(
            '#update', [
            'name'               => 'Updated camera',
            'account_id'         => 2,
            'targetamount'       => 2000,
            'targetdate'         => '',
            'reminder'           => 'week',
            'post_submit_action' => 'update',
        ]
        );
        $I->see('Piggy bank &quot;Updated camera&quot; updated.');


    }

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturn(FunctionalTester $I)
    {
        $I->wantTo('update a piggy bank and return');
        $I->amOnPage('/piggy_banks/edit/1');
        $I->see('Edit piggy bank "New camera"');
        $I->submitForm(
            '#update', [
                         'name'               => 'Updated camera',
                         'account_id'         => 2,
                         'targetamount'       => 2000,
                         'targetdate'         => '',
                         'reminder'           => 'week',
                         'post_submit_action' => 'return_to_edit',
                     ]
        );
        $I->see('Piggy bank &quot;Updated camera&quot; updated.');


    }

    /**
     * @param FunctionalTester $I
     */
    public function updateValidateOnly(FunctionalTester $I)
    {
        $I->wantTo('validate a piggy bank');
        $I->amOnPage('/piggy_banks/edit/1');
        $I->see('Edit piggy bank "New camera"');
        $I->submitForm(
            '#update', [
                         'name'               => 'Updated camera',
                         'account_id'         => 2,
                         'targetamount'       => 2000,
                         'targetdate'         => '',
                         'reminder'           => 'week',
                         'post_submit_action' => 'validate_only',
                     ]
        );
        $I->see('Updated camera');


    }

    /**
     * @param FunctionalTester $I
     */
    public function updateFail(FunctionalTester $I)
    {
        $I->wantTo('update a piggy bank and fail');
        $I->amOnPage('/piggy_banks/edit/1');
        $I->see('Edit piggy bank "New camera"');
        $I->submitForm(
            '#update', [
            'name'               => '',
            'account_id'         => 2,
            'targetamount'       => 2000,
            'targetdate'         => '',
            'reminder'           => 'week',
            'post_submit_action' => 'update',
        ]
        );
        $I->see('Name is too short');
        $I->seeInDatabase('piggy_banks', ['name' => 'New camera']);

    }

}