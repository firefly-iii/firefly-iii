<?php

class TransactionJournalTest extends TestCase
{
    /**
     * Default preparation for each test
     */
    public function setUp()
    {
        parent::setUp();

        $this->prepareForTests();
    }

    /**
     * Migrate the database
     */
    private function prepareForTests()
    {
        Artisan::call('migrate');
    }

    /**
     * Test accounts
     */
    public function testJournal()
    {
        $this->assertTrue(true);
    }
}