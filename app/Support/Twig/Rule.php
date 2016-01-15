<?php

namespace FireflyIII\Support\Twig;

use Config;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class Rule
 *
 * @package FireflyIII\Support\Twig
 */
class Rule extends Twig_Extension
{

    /**
     * @return Twig_SimpleFunction
     */
    public function allJournalTriggers()
    {
        return new Twig_SimpleFunction(
            'allJournalTriggers', function () {
            return [
                'store-journal'  => trans('firefly.rule_trigger_store_journal'),
                'update-journal' => trans('firefly.rule_trigger_update_journal'),
            ];
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function allRuleTriggers()
    {
        return new Twig_SimpleFunction(
            'allRuleTriggers', function () {
            $ruleTriggers     = array_keys(Config::get('firefly.rule-triggers'));
            $possibleTriggers = [];
            foreach ($ruleTriggers as $key) {
                if ($key != 'user_action') {
                    $possibleTriggers[$key] = trans('firefly.rule_trigger_' . $key . '_choice');
                }
            }
            unset($key, $ruleTriggers);

            return $possibleTriggers;
        }

        );

    }

    /**
     * @return Twig_SimpleFunction
     */
    public function allActionTriggers()
    {
        return new Twig_SimpleFunction(
            'allRuleActions', function () {
            // array of valid values for actions
            $ruleActions     = array_keys(Config::get('firefly.rule-actions'));
            $possibleActions = [];
            foreach ($ruleActions as $key) {
                $possibleActions[$key] = trans('firefly.rule_action_' . $key . '_choice');
            }
            unset($key, $ruleActions);

            return $possibleActions;
        }
        );
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            $this->allJournalTriggers(),
            $this->allRuleTriggers(),
            $this->allActionTriggers(),
        ];

    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'FireflyIII\Support\Twig\Rule';
    }
}