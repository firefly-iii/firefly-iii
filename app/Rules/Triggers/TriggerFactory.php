<?php
declare(strict_types = 1);
/**
 * TriggerFactory.php
 * Copyright (C) 2016 Robert Horlings
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Domain;

/**
 * Interface TriggerInterface
 *
 * @package FireflyIII\Rules\Triggers
 */
class TriggerFactory
{
    protected static $triggerTypes = null;

    /**
     * Returns the class name to be used for triggers with the given name
     *
     * @param string $triggerType
     *
     * @return TriggerInterface
     */
    public static function getTriggerClass(string $triggerType): string
    {
        $triggerTypes = self::getTriggerTypes();

        if (!array_key_exists($triggerType, $triggerTypes)) {
            abort(500, 'No such trigger exists ("' . $triggerType . '").');
        }

        $class = $triggerTypes[$triggerType];
        if (!class_exists($class)) {
            abort(500, 'Could not instantiate class for rule trigger type "' . $triggerType . '" (' . $class . ').');
        }

        return $class;
    }

    /**
     * Returns the trigger for the given type and journal
     *
     * @param RuleTrigger        $trigger
     * @param TransactionJournal $journal
     *
     * @return TriggerInterface
     */
    public static function getTrigger(RuleTrigger $trigger, TransactionJournal $journal): TriggerInterface
    {
        $triggerType = $trigger->trigger_type;
        $class       = self::getTriggerClass($triggerType);

        return new $class($trigger, $journal);
    }

    /**
     * Returns a map with triggertypes, mapped to the class representing that type
     */
    protected static function getTriggerTypes()
    {
        if (!self::$triggerTypes) {
            self::$triggerTypes = Domain::getRuleTriggers();
        }

        return self::$triggerTypes;
    }
}
