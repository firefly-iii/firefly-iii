<?php

namespace FireflyIII\Support\Import\Routine\Fake;

/**
 * Class StageFinalHandler
 *
 * @package FireflyIII\Support\Import\Routine\Fake
 */
class StageFinalHandler
{
    /**
     * @return array
     */
    public function getTransactions(): array
    {
        $transactions = [];

        for ($i = 0; $i < 5; $i++) {
            $transaction = [];


            $transactions[] = $transaction;
        }


        return $transactions;

    }

}