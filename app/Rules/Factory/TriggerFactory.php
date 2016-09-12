<?php
/**
 * TriggerFactory.php
 * Copyright (C) 2016 Robert Horlings
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Rules\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Rules\Triggers\AbstractTrigger;
use FireflyIII\Rules\Triggers\TriggerInterface;
use FireflyIII\Support\Domain;
use Log;

/**
 * Interface TriggerInterface
 *
 * @package FireflyIII\Rules\Triggers
 */
class TriggerFactory
{
    protected static $triggerTypes = [];

    /**
     * Returns the trigger for the given type and journal. This method returns the actual implementation
     * (Rules/Triggers/[object]) for a given RuleTrigger (database object). If for example the database object
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
        $class = self::getTriggerClass($triggerType);
        $obj   = $class::makeFromTriggerValue($trigger->trigger_value);

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
     * @return AbstractTrigger
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
        if (count(self::$triggerTypes) === 0) {
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
