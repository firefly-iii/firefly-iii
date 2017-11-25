<?php
/**
 * TriggerFactory.php
 * Copyright (C) 2016 Robert Horlings.
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Support\Domain;
use FireflyIII\TransactionRules\Triggers\AbstractTrigger;
use FireflyIII\TransactionRules\Triggers\TriggerInterface;
use Log;

/**
 * @codeCoverageIgnore
 *
 * Class TriggerFactory can create triggers.
 */
class TriggerFactory
{
    /** @var array array with trigger types */
    protected static $triggerTypes = [];

    /**
     * Returns the trigger for the given type and journal. This method returns the actual implementation
     * (TransactionRules/Triggers/[object]) for a given RuleTrigger (database object). If for example the database object
     * contains trigger_type "description_is" with value "Rent" this method will return a corresponding
     * DescriptionIs object preset to "Rent". Any transaction journal then fed to this object will
     * be triggered if its description actually is "Rent".
     *
     * @param RuleTrigger $trigger
     *
     * @return AbstractTrigger
     */
    public static function getTrigger(RuleTrigger $trigger)
    {
        $triggerType = $trigger->trigger_type;

        /** @var AbstractTrigger $class */
        $class               = self::getTriggerClass($triggerType);
        $obj                 = $class::makeFromTriggerValue($trigger->trigger_value);
        $obj->stopProcessing = $trigger->stop_processing;

        Log::debug(sprintf('self::getTriggerClass("%s") = "%s"', $triggerType, $class));
        Log::debug(sprintf('%s::makeFromTriggerValue(%s) = object of class "%s"', $class, $trigger->trigger_value, get_class($obj)));

        return $obj;
    }

    /**
     * This method is equal to TriggerFactory::getTrigger but accepts a textual representation of the trigger type
     * (for example "description_is"), the trigger value ("Rent") and whether or not Firefly III should stop processing
     * other triggers (if present) after this trigger.
     *
     * This method is used when the RuleTriggers from TriggerFactory::getTrigger do not exist (yet).
     *
     * @param string $triggerType
     * @param string $triggerValue
     * @param bool   $stopProcessing
     *
     * @see TriggerFactory::getTrigger
     *
     * @return AbstractTrigger
     *
     * @throws FireflyException
     */
    public static function makeTriggerFromStrings(string $triggerType, string $triggerValue, bool $stopProcessing)
    {
        /** @var AbstractTrigger $class */
        $class = self::getTriggerClass($triggerType);
        $obj   = $class::makeFromStrings($triggerValue, $stopProcessing);
        Log::debug('Created trigger from string', ['type' => $triggerType, 'value' => $triggerValue, 'stopProcessing' => $stopProcessing, 'class' => $class]);

        return $obj;
    }

    /**
     * Returns a map with trigger types, mapped to the class representing that type.
     *
     * @return array
     */
    protected static function getTriggerTypes(): array
    {
        if (0 === count(self::$triggerTypes)) {
            self::$triggerTypes = Domain::getRuleTriggers();
        }

        return self::$triggerTypes;
    }

    /**
     * Returns the class name to be used for triggers with the given name. This is a lookup function
     * that will match the given trigger type (ie. "from_account_ends") to the matching class name
     * (FromAccountEnds) using the configuration (firefly.php).
     *
     * @param string $triggerType
     *
     * @return TriggerInterface|string
     *
     * @throws FireflyException
     */
    private static function getTriggerClass(string $triggerType): string
    {
        $triggerTypes = self::getTriggerTypes();

        if (!array_key_exists($triggerType, $triggerTypes)) {
            throw new FireflyException('No such trigger exists ("' . e($triggerType) . '").');
        }

        $class = $triggerTypes[$triggerType];
        if (!class_exists($class)) {
            throw new FireflyException('Could not instantiate class for rule trigger type "' . e($triggerType) . '" (' . e($class) . ').');
        }

        return $class;
    }
}
