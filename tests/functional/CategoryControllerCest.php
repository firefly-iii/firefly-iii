<?php

/**
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class CategoryControllerCest
 */
class CategoryControllerCest
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
        $I->wantTo('create a new category');
        $I->amOnPage('/categories/create');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a category');
        $I->amOnPage('/categories/delete/4');
        $I->see('Delete category "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a category');
        $I->amOnPage('/categories/delete/4');
        $I->see('Delete category "Delete me"');
        $I->submitForm('#destroy', []);
        $I->see('Category &quot;Delete me&quot; was deleted.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a category');
        $I->amOnPage('/categories/edit/4');
        $I->see('Edit category "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('show all categories');
        $I->amOnPage('/categories');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('show a category');
        $I->amOnPage('/categories/show/4');
        $I->see('Delete me');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->amOnPage('/categories/create');
        $I->wantTo('store a new category');
        $I->see('Create a new category');
        $I->submitForm('#store', ['name' => 'New category.', 'post_submit_action' => 'store']);
        $I->seeRecord('categories', ['name' => 'New category.']);
        resetToClean::clean();
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeValidateOnly(FunctionalTester $I)
    {
        $I->amOnPage('/categories/create');
        $I->wantTo('validate a new category');
        $I->see('Create a new category');
        $I->submitForm('#store', ['name' => 'New category.', 'post_submit_action' => 'validate_only']);
        $I->dontSeeRecord('categories', ['name' => 'New category.']);
        resetToClean::clean();
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeAndCreateAnother(FunctionalTester $I)
    {
        $I->amOnPage('/categories/create');
        $I->wantTo('store a new category and create another');
        $I->see('Create a new category');
        $I->submitForm('#store', ['name' => 'New category.', 'post_submit_action' => 'create_another']);
        $I->seeRecord('categories', ['name' => 'New category.']);
        resetToClean::clean();
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeFail(FunctionalTester $I)
    {
        $I->amOnPage('/categories/create');
        $I->wantTo('make storing a new category fail.');
        $I->see('Create a new category');
        $I->submitForm('#store', ['name' => null,  'post_submit_action' => 'validate_only']);
        $I->dontSeeRecord('categories', ['name' => 'New category.']);
        resetToClean::clean();
    }
    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a category');
        $I->amOnPage('/categories/edit/4');
        $I->see('Edit category "Delete me"');
        $I->submitForm('#update', ['name' => 'Update me', 'post_submit_action' => 'update']);
        $I->seeRecord('categories', ['name' => 'Update me']);
        resetToClean::clean();

    }

    /**
     * @param FunctionalTester $I
     */
    public function failUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a category and fail');
        $I->amOnPage('/categories/edit/4');
        $I->see('Edit category "Delete me"');
        $I->submitForm('#update', ['name' => '', 'post_submit_action' => 'update']);
        $I->seeRecord('categories', ['name' => 'Delete me']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function validateUpdateOnly(FunctionalTester $I)
    {
        $I->wantTo('update a category and validate only');
        $I->amOnPage('/categories/edit/4');
        $I->see('Edit category "Delete me"');
        $I->submitForm(
            '#update', ['name' => 'Validate Only', 'post_submit_action' => 'validate_only']
        );
        $I->dontSeeRecord('categories', ['name' => 'Savings accountXX']);
        $I->seeRecord('categories', ['name' => 'Delete me']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturn(FunctionalTester $I)
    {
        $I->wantTo('update a category and return to form');
        $I->amOnPage('/categories/edit/4');
        $I->see('Edit category "Delete me"');
        $I->submitForm(
            '#update', ['name' => 'Savings accountXX',  'post_submit_action' => 'return_to_edit']
        );
        $I->seeRecord('categories', ['name' => 'Savings accountXX']);

    }

}