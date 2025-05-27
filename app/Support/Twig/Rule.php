<?php

/**
 * Rule.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Config;
use Override;

/**
 * Class Rule.
 */
class Rule extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            $this->allJournalTriggers(),
            $this->allRuleTriggers(),
            $this->allActionTriggers(),
        ];
    }

    public function allJournalTriggers(): TwigFunction
    {
        return new TwigFunction(
            'allJournalTriggers',
            static fn () => [
                'store-journal'     => (string) trans('firefly.rule_trigger_store_journal'),
                'update-journal'    => (string) trans('firefly.rule_trigger_update_journal'),
                'manual-activation' => (string) trans('firefly.rule_trigger_manual'),
            ]
        );
    }

    public function allRuleTriggers(): TwigFunction
    {
        return new TwigFunction(
            'allRuleTriggers',
            static function () {
                $ruleTriggers     = array_keys(config('search.operators'));
                $possibleTriggers = [];
                foreach ($ruleTriggers as $key) {
                    if ('user_action' !== $key) {
                        $possibleTriggers[$key] = (string) trans('firefly.rule_trigger_'.$key.'_choice');
                    }
                }
                unset($ruleTriggers);
                asort($possibleTriggers);

                return $possibleTriggers;
            }
        );
    }

    public function allActionTriggers(): TwigFunction
    {
        return new TwigFunction(
            'allRuleActions',
            static function () {
                // array of valid values for actions
                $ruleActions     = array_keys(Config::get('firefly.rule-actions'));
                $possibleActions = [];
                foreach ($ruleActions as $key) {
                    $possibleActions[$key] = (string) trans('firefly.rule_action_'.$key.'_choice');
                }
                unset($ruleActions);
                asort($possibleActions);

                return $possibleActions;
            }
        );
    }
}
