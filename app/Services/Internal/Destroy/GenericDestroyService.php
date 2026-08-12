<?php




declare(strict_types=1);



namespace FireflyIII\Services\Internal\Destroy;

use FireflyIII\Models\RuleTrigger;

class GenericDestroyService
{
    public function deleteRuleTrigger(RuleTrigger $ruleTrigger): void
    {
        $ruleTrigger->forceDelete();
    }
}
