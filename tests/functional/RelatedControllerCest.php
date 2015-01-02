<?php

/**
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class RelatedControllerCest
 */
class RelatedControllerCest
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

    public function alreadyRelated(FunctionalTester $I)
    {
        $group   = TransactionGroup::first();
        $journal = $group->transactionjournals()->first();

        $I->wantTo('see already related transactions');
        $I->amOnPage('/related/alreadyRelated/' . $journal->id);
        $I->see('Big expense in ');

    }

    public function alreadyRelatedNoRelations(FunctionalTester $I)
    {
        $journal = TransactionJournal::first();

        $I->wantTo('see already related transactions for a journal without any');
        $I->amOnPage('/related/alreadyRelated/' . $journal->id);
        $I->see('[]');

    }

    public function relate(FunctionalTester $I)
    {
        $journal      = TransactionJournal::leftJoin(
            'transaction_group_transaction_journal', 'transaction_journals.id', '=', 'transaction_group_transaction_journal.transaction_journal_id'
        )
                                          ->whereNull('transaction_group_transaction_journal.transaction_group_id')->first(['transaction_journals.*']);
        $otherJournal = TransactionJournal::leftJoin(
            'transaction_group_transaction_journal', 'transaction_journals.id', '=', 'transaction_group_transaction_journal.transaction_journal_id'
        )
                                          ->whereNull('transaction_group_transaction_journal.transaction_group_id')->where(
                'transaction_journals.id', '!=', $journal->id
            )->first(
                ['transaction_journals.*']
            );
        $I->wantTo('relate two journals');
        $I->sendAjaxPostRequest('/related/relate/' . $journal->id . '/' . $otherJournal->id);
        $I->see('true');
    }

    public function related(FunctionalTester $I)
    {
        $group   = TransactionGroup::first();
        $journal = $group->transactionjournals()->first();

        $I->wantTo('see the popup with already related transactions');
        $I->amOnPage('/related/related/' . $journal->id);
        $I->see('Big expense in ');
    }

    public function removeRelation(FunctionalTester $I)
    {
        $group = TransactionGroup::first();
        $one   = $group->transactionjournals[0];
        $two   = $group->transactionjournals[1];
        $I->wantTo('relate two journals');
        $I->amOnPage('/related/removeRelation/' . $one->id . '/' . $two->id);
        $I->see('true');

    }

    public function search(FunctionalTester $I)
    {
        $group = TransactionGroup::first();
        $one   = $group->transactionjournals[0];

        $I->wantTo('search for a transaction to relate');

        $I->sendAjaxPostRequest('/related/search/' . $one->id . '?searchValue=expense');
        $I->see('Big expense in');
    }
}