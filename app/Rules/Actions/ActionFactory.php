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
     * Returns the class name to be used for actions with the given name
     * @param string $actionType
     * @return ActionInterface
     */
    public static function getActionClass(string $actionType): string {
        $actionTypes = self::getActionTypes();
        
        if (!array_key_exists($actionType, $actionTypes)) {
            abort(500, 'No such action exists ("' . $actionType . '").');
        }
        
        $class = $actionTypes[$actionType];
        if (!class_exists($class)) {
            abort(500, 'Could not instantiate class for rule action type "' . $actionType . '" (' . $class . ').');
        }
        
        return $class;
    }
    
    /**
     * Returns the action for the given type and journal
     * @param RuleAction $action
     * @param TransactionJournal $journal
     * @return ActionInterface
     */
    public static function getAction(RuleAction $action, TransactionJournal $journal): ActionInterface {
        $actionType = $action->action_type;
        $class = self::getActionClass($actionType);
        
        return new $class($action, $journal);
    }
    
    /**
     * Returns a map with actiontypes, mapped to the class representing that type
     */
    protected static function getActionTypes() {
        if( !self::$actionTypes ) {
            self::$actionTypes = Domain::getRuleActions();
        }
        
        return self::$actionTypes;
    }
}
