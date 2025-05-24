<?php

/**
 * AddTag.php
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

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Facades\DB;

/**
 * Class AddTag.
 */
class AddTag implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        // journal has this tag maybe?
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);

        /** @var User $user */
        $user    = User::find($journal['user_id']);
        $factory->setUser($user);
        $tagName = $this->action->getValue($journal);
        $tag     = $factory->findOrCreate($tagName);

        if (null === $tag) {
            // could not find, could not create tag.
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.find_or_create_tag_failed', ['tag' => $tagName])));

            return false;
        }

        $count   = DB::table('tag_transaction_journal')
            ->where('tag_id', $tag->id)
            ->where('transaction_journal_id', $journal['transaction_journal_id'])
            ->count()
        ;
        if (0 === $count) {
            // add to journal:
            DB::table('tag_transaction_journal')->insert(['tag_id' => $tag->id, 'transaction_journal_id' => $journal['transaction_journal_id']]);
            app('log')->debug(sprintf('RuleAction AddTag. Added tag #%d ("%s") to journal %d.', $tag->id, $tag->tag, $journal['transaction_journal_id']));

            /** @var TransactionJournal $object */
            $object = TransactionJournal::find($journal['transaction_journal_id']);

            // event for audit log entry
            event(new TriggeredAuditLog($this->action->rule, $object, 'add_tag', null, $tag->tag));

            return true;
        }
        app('log')->debug(
            sprintf('RuleAction AddTag fired but tag %d ("%s") was already added to journal %d.', $tag->id, $tag->tag, $journal['transaction_journal_id'])
        );
        event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.tag_already_added', ['tag' => $tagName])));

        return false;
    }
}
