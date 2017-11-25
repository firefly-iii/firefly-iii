<?php
/**
 * ActionFactory.php
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
use FireflyIII\Models\RuleAction;
use FireflyIII\Support\Domain;
use FireflyIII\TransactionRules\Actions\ActionInterface;
use Log;

/**
 * @codeCoverageIgnore
 *
 * Class ActionFactory can create actions.
 */
class ActionFactory
{
    /** @var array array of action types */
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
     *
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
     * Returns a map with actiontypes, mapped to the class representing that type.
     *
     * @return array
     */
    protected static function getActionTypes(): array
    {
        if (0 === count(self::$actionTypes)) {
            self::$actionTypes = Domain::getRuleActions();
        }

        return self::$actionTypes;
    }
}
