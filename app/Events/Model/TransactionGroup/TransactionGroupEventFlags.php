<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\TransactionGroup;

class TransactionGroupEventFlags
{
    public bool $applyRules        = true;
    public bool $fireWebhooks      = true;
    public bool $batchSubmission   = false;
    public bool $recalculateCredit = true;
    public bool $unifyOnly         = false;
}
