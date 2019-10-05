<?php
/**
 * RuleEngine.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\TransactionRules\Engine;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepository;
use FireflyIII\TransactionRules\Processor;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RuleEngine
 *
 * Set the user, then apply an array to setRulesToApply(array) or call addRuleIdToApply(int) or addRuleToApply(Rule).
 * Then call process() to make the magic happen.
 *
 */
class RuleEngine
{
    /** @var int */
    public const TRIGGER_STORE = 1;
    /** @var int */
    public const TRIGGER_UPDATE = 2;
    /** @var bool */
    private $allRules;
    /** @var RuleGroupRepository */
    private $ruleGroupRepository;
    /** @var Collection */
    private $ruleGroups;
    /** @var array */
    private $rulesToApply;
    /** @var int */
    private $triggerMode;
    /** @var User */
    private $user;

    /**
     * RuleEngine constructor.
     */
    public function __construct()
    {
        Log::debug('Created RuleEngine');
        $this->ruleGroups          = new Collection;
        $this->rulesToApply        = [];
        $this->allRules            = false;
        $this->ruleGroupRepository = app(RuleGroupRepository::class);
        $this->triggerMode         = self::TRIGGER_STORE;
    }

    /**
     * @param array $journal
     */
    public function processJournalArray(array $journal): void
    {
        $journalId = $journal['id'] ?? $journal['transaction_journal_id'];
        Log::debug(sprintf('Will process transaction journal #%d ("%s")', $journalId, $journal['description']));
        /** @var RuleGroup $group */
        foreach ($this->ruleGroups as $group) {
            Log::debug(sprintf('Now at rule group #%d', $group->id));
            $groupTriggered = false;
            /** @var Rule $rule */
            foreach ($group->rules as $rule) {
                Log::debug(sprintf('Now at rule #%d from rule group #%d', $rule->id, $group->id));
                $ruleTriggered = false;
                // if in rule selection, or group in selection or all rules, it's included.
                if ($this->includeRule($rule)) {
                    Log::debug(sprintf('Rule #%d is included.', $rule->id));
                    /** @var Processor $processor */
                    $processor     = app(Processor::class);
                    $ruleTriggered = false;
                    try {
                        $processor->make($rule, true);
                        $ruleTriggered = $processor->handleJournalArray($journal);
                    } catch (FireflyException $e) {
                        Log::error($e->getMessage());
                    }

                    if ($ruleTriggered) {
                        Log::debug('The rule was triggered, so the group is as well!');
                        $groupTriggered = true;
                    }
                }
                if (!$this->includeRule($rule)) {
                    Log::debug(sprintf('Rule #%d is not included.', $rule->id));
                }

                // if the rule is triggered and stop processing is true, cancel the entire group.
                if ($ruleTriggered && $rule->stop_processing) {
                    Log::info(sprintf('Break out group #%d because rule #%d was triggered.', $group->id, $rule->id));
                    break;
                }
            }
            // if group is triggered and stop processing is true, cancel the whole thing.
            if ($groupTriggered && $group->stop_processing) {
                Log::info(sprintf('Break out ALL because group #%d was triggered.', $group->id));
                break;
            }
        }
        Log::debug('Done processing this transaction journal.');
    }

    /**
     * @param TransactionJournal $transactionJournal
     */
    public function processTransactionJournal(TransactionJournal $transactionJournal): void
    {
        Log::debug(sprintf('Will process transaction journal #%d ("%s")', $transactionJournal->id, $transactionJournal->description));
        /** @var RuleGroup $group */
        foreach ($this->ruleGroups as $group) {
            Log::debug(sprintf('Now at rule group #%d', $group->id));
            $groupTriggered = false;
            /** @var Rule $rule */
            foreach ($group->rules as $rule) {
                Log::debug(sprintf('Now at rule #%d from rule group #%d', $rule->id, $group->id));
                $ruleTriggered = false;
                // if in rule selection, or group in selection or all rules, it's included.
                if ($this->includeRule($rule)) {
                    Log::debug(sprintf('Rule #%d is included.', $rule->id));
                    /** @var Processor $processor */
                    $processor     = app(Processor::class);
                    $ruleTriggered = false;
                    try {
                        $processor->make($rule, true);
                        $ruleTriggered = $processor->handleTransactionJournal($transactionJournal);
                    } catch (FireflyException $e) {
                        Log::error($e->getMessage());
                    }

                    if ($ruleTriggered) {
                        Log::debug('The rule was triggered, so the group is as well!');
                        $groupTriggered = true;
                    }
                }
                if (!$this->includeRule($rule)) {
                    Log::debug(sprintf('Rule #%d is not included.', $rule->id));
                }

                // if the rule is triggered and stop processing is true, cancel the entire group.
                if ($ruleTriggered && $rule->stop_processing) {
                    Log::info(sprintf('Break out group #%d because rule #%d was triggered.', $group->id, $rule->id));
                    break;
                }
            }
            // if group is triggered and stop processing is true, cancel the whole thing.
            if ($groupTriggered && $group->stop_processing) {
                Log::info(sprintf('Break out ALL because group #%d was triggered.', $group->id));
                break;
            }
        }
        Log::debug('Done processing this transaction journal.');
    }

    /**
     * @param bool $allRules
     */
    public function setAllRules(bool $allRules): void
    {
        Log::debug('RuleEngine will apply ALL rules.');
        $this->allRules = $allRules;
    }

    /**
     * @param array $rulesToApply
     */
    public function setRulesToApply(array $rulesToApply): void
    {
        Log::debug('RuleEngine will try rules', $rulesToApply);
        $this->rulesToApply = $rulesToApply;
    }

    /**
     * @param int $triggerMode
     */
    public function setTriggerMode(int $triggerMode): void
    {
        $this->triggerMode = $triggerMode;
    }

    /**
     * @param User|Authenticatable $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->ruleGroupRepository->setUser($user);
        $this->ruleGroups = $this->ruleGroupRepository->getActiveGroups();
    }

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    private function includeRule(Rule $rule): bool
    {
        /** @var RuleTrigger $trigger */
        $trigger = $rule->ruleTriggers()->where('trigger_type', 'user_action')->first();
        if (null === $trigger) {
            return false;
        }

        $validTrigger = ('store-journal' === $trigger->trigger_value && self::TRIGGER_STORE === $this->triggerMode)
                        || ('update-journal' === $trigger->trigger_value && self::TRIGGER_UPDATE === $this->triggerMode);

        return $validTrigger && ($this->allRules || in_array($rule->id, $this->rulesToApply, true));
    }

}
