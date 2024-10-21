<?php

declare(strict_types=1);
/*
 * MigrateRuleActions.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\RuleAction;
use Illuminate\Console\Command;

class MigrateRuleActions extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '610_migrate_rule_actions';

    protected $description          = 'Migrate rule actions away from expression engine';

    protected $signature            = 'firefly-iii:migrate-rule-actions {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        if (false === config('firefly.feature_flags.expression_engine')) {
            $this->friendlyInfo('Expression engine is not enabled. Nothing to do.');

            return 0;
        }
        $this->replaceEqualSign();
        $this->replaceObsoleteActions();
        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    private function replaceEqualSign(): void
    {
        $count   = 0;
        $actions = RuleAction::get();

        /** @var RuleAction $action */
        foreach ($actions as $action) {
            if (str_starts_with($action->action_value, '=')) {
                $action->action_value = sprintf('%s%s', '\=', substr($action->action_value, 1));
                $action->save();
                ++$count;
            }
        }
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Upgrading %d rule action(s) for the new expression engine.', $count));
        }
        if (0 === $count) {
            $this->friendlyInfo('All rule actions are up to date.');
        }
    }

    private function replaceObsoleteActions(): void
    {
        $obsolete = [
            'append_description',
            'prepend_description',
            'append_notes',
            'prepend_notes',
            'append_descr_to_notes',
            'append_notes_to_descr',
            'move_descr_to_notes',
            'move_notes_to_descr',
        ];
        $actions  = RuleAction::whereIn('action_type', $obsolete)->get();

        /** @var RuleAction $action */
        foreach ($actions as $action) {
            $oldType = $action->action_type;

            switch ($action->action_type) {
                default:
                    $this->friendlyError(sprintf('Cannot deal with action type "%s", skip it.', $action->action_type));

                    break;

                case 'append_description':
                    $action->action_type  = 'set_description';
                    $action->action_value = sprintf('=description~"%s"', str_replace('"', '\"', $action->action_value));
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'prepend_description':
                    $action->action_type  = 'set_description';
                    $action->action_value = sprintf('="%s"~description', str_replace('"', '\"', $action->action_value));
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'append_notes':
                    $action->action_type  = 'set_notes';
                    $action->action_value = sprintf('=notes~"%s"', str_replace('"', '\"', $action->action_value));
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'prepend_notes':
                    $action->action_type  = 'set_notes';
                    $action->action_value = sprintf('="%s"~notes', str_replace('"', '\"', $action->action_value));
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'append_descr_to_notes':
                    $action->action_type  = 'set_notes';
                    $action->action_value = '=notes~" "~description';
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'append_notes_to_descr':
                    $action->action_type  = 'set_description';
                    $action->action_value = '=description~" "~notes';
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'move_descr_to_notes':
                    $action->action_type  = 'set_notes';
                    $action->action_value = '=description';
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;

                case 'move_notes_to_descr':
                    $action->action_type  = 'set_description';
                    $action->action_value = '=notes';
                    $action->save();
                    $this->friendlyInfo(sprintf('Upgraded action #%d from "%s" to "%s".', $action->id, $oldType, $action->action_type));

                    break;
            }
        }
    }
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
