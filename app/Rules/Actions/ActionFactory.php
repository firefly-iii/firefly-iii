<?php
declare(strict_types = 1);
/**
 * ActionFactory.php
 * Copyright (C) 2016 Robert Horlings
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Actions;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Domain;

/**
 * Interface ActionInterface
 *
 * @package FireflyIII\Rules\Actions
 */
class ActionFactory
{
    protected static $actionTypes = null;

    /**
     * Returns the action for the given type and journal
     *
     * @param RuleAction         $action
     * @param TransactionJournal $journal
     *
     * @return ActionInterface
     */
    public static function getAction(RuleAction $action, TransactionJournal $journal): ActionInterface
    {
        $actionType = $action->action_type;
        $class      = self::getActionClass($actionType);

        return new $class($action, $journal);
    }

    /**
     * Returns the class name to be used for actions with the given name
     *
     * @param string $actionType
     *
     * @return ActionInterface|string
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
     */
    protected static function getActionTypes()
    {
        if (!self::$actionTypes) {
            self::$actionTypes = Domain::getRuleActions();
        }

        return self::$actionTypes;
    }
}
