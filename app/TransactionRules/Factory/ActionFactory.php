<?php
/**
 * ActionFactory.php
 * Copyright (C) 2016 Robert Horlings
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\RuleAction;
use FireflyIII\TransactionRules\Actions\ActionInterface;
use FireflyIII\Support\Domain;
use Log;

/**
 * Interface ActionInterface
 *
 * @package FireflyIII\TransactionRules\Actions
 */
class ActionFactory
{
    protected static $actionTypes = [];

    /**
     * This method returns the actual implementation (TransactionRules/Actions/[object]) for a given
     * RuleAction (database object). If for example the database object contains action_type "change_category"
     * with value "Groceries" this method will return a corresponding SetCategory object preset
     * to "Groceries". Any transaction journal then fed to this object will have its category changed.
     *
     * @param RuleAction $action
     *
     * @return ActionInterface
     */
    public static function getAction(RuleAction $action): ActionInterface
    {
        $class = self::getActionClass($action->action_type);
        Log::debug(sprintf('self::getActionClass("%s") = "%s"', $action->action_type, $class));

        return new $class($action);
    }

    /**
     * Returns the class name to be used for actions with the given name. This is a lookup function
     * that will match the given action type (ie. "change_category") to the matching class name
     * (SetCategory) using the configuration (firefly.php).
     *
     * @param string $actionType
     *
     * @return string
     * @throws FireflyException
     */
    public static function getActionClass(string $actionType): string
    {
        $actionTypes = self::getActionTypes();

        if (!array_key_exists($actionType, $actionTypes)) {
            throw new FireflyException('No such action exists ("' . e($actionType) . '").');
        }

        $class = $actionTypes[$actionType];
        if (!class_exists($class)) {
            throw new FireflyException('Could not instantiate class for rule action type "' . e($actionType) . '" (' . e($class) . ').');
        }

        return $class;
    }

    /**
     * Returns a map with actiontypes, mapped to the class representing that type
     *
     * @return array
     */
    protected static function getActionTypes(): array
    {
        if (count(self::$actionTypes) === 0) {
            self::$actionTypes = Domain::getRuleActions();
        }

        return self::$actionTypes;
    }
}
