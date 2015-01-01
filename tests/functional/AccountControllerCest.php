<?php

/**
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class AccountControllerCest
 */
class AccountControllerCest
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
        // @codingStandardsIgnoreStart
        $I->wantTo('create a new asset account');
        $I->amOnPage('/accounts/create/asset');
        $I->see('Create a new asset account');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete an asset account');
        $I->amOnPage('/accounts/delete/3');
        $I->see('Delete account "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy an asset account');
        $I->amOnPage('/accounts/delete/3');
        $I->see('Delete account "Delete me"');
        $I->submitForm('#destroy', []);
        $I->dontSeeRecord('accounts', ['id' => 3, 'deleted_at' => null]);
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit an asset account');
        $I->amOnPage('/accounts/edit/3');
        $I->see('Edit asset account "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function failUpdate(FunctionalTester $I)
    {
        $I->wantTo('update an asset account and fail');
        $I->amOnPage('/accounts/edit/3');
        $I->see('Edit asset account "Delete me"');
        $I->submitForm('#update', ['name' => '', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'update']);
        $I->seeRecord('accounts', ['name' => 'Delete me']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see a list of accounts');
        $I->amOnPage('/accounts/asset');
        $I->see('Checking account');
        $I->see('Delete me');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('see one account');
        $I->amOnPage('/accounts/show/3');
        $I->see('Details for');
        $I->see('Delete me');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->amOnPage('/accounts/create/asset');
        $I->wantTo('store a new asset account');
        $I->see('Create a new asset account');
        $I->submitForm('#store', ['name' => 'New through tests.', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'store']);
        $I->seeRecord('accounts', ['name' => 'New through tests.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeAndCreateAnother(FunctionalTester $I)
    {
        $I->amOnPage('/accounts/create/asset');
        $I->wantTo('store a new asset account and create another');
        $I->see('Create a new asset account');
        $I->submitForm(
            '#store', ['name' => 'New through tests.', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'create_another']
        );
        $I->seeRecord('accounts', ['name' => 'New through tests.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeFail(FunctionalTester $I)
    {
        $I->amOnPage('/accounts/create/asset');
        $I->wantTo('make storing a new asset account fail.');
        $I->see('Create a new asset account');
        $I->submitForm('#store', ['name' => null, 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'validate_only']);
        $I->dontSeeRecord('accounts', ['name' => 'New through tests.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeValidateOnly(FunctionalTester $I)
    {
        $I->amOnPage('/accounts/create/asset');
        $I->wantTo('validate a new asset account');
        $I->see('Create a new asset account');
        $I->submitForm(
            '#store', ['name' => 'New through tests.', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'validate_only']
        );
        $I->dontSeeRecord('accounts', ['name' => 'New through tests.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update an asset account');
        $I->amOnPage('/accounts/edit/3');
        $I->see('Edit asset account "Delete me"');
        $I->submitForm('#update', ['name' => 'Update me', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'update']);
        $I->seeRecord('accounts', ['name' => 'Update me']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturn(FunctionalTester $I)
    {
        $I->wantTo('update an asset account and return to form');
        $I->amOnPage('/accounts/edit/2');
        $I->see('Edit asset account "Savings account"');
        $I->submitForm(
            '#update', ['name' => 'Savings accountXX', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'return_to_edit']
        );
        $I->seeRecord('accounts', ['name' => 'Savings accountXX']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function validateUpdateOnly(FunctionalTester $I)
    {
        $I->wantTo('update an asset account and validate only');
        $I->amOnPage('/accounts/edit/2');
        $I->see('Edit asset account "Savings account"');
        $I->submitForm(
            '#update', ['name' => 'Savings accountXX', 'what' => 'asset', 'account_role' => 'defaultExpense', 'post_submit_action' => 'validate_only']
        );
        $I->dontSeeRecord('accounts', ['name' => 'Savings accountXX']);

    }

}