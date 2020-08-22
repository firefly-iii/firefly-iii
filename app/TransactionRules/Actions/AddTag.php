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

use DB;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Log;

/**
 * Class AddTag.
 */
class AddTag implements ActionInterface
{
    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function act(TransactionJournal $journal): bool
    {
        // journal has this tag maybe?
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($journal->user);
        $tag = $factory->findOrCreate($this->action->action_value);
        if (null === $tag) {
            // could not find, could not create tag.
            Log::error(sprintf('RuleAction AddTag. Could not find or create tag "%s"', $this->action->action_value));

            return false;
        }
        $count = $journal->tags()->where('tag_id', $tag->id)->count();
        if (0 === $count) {
            $journal->tags()->save($tag);
            $journal->touch();
            Log::debug(sprintf('RuleAction AddTag. Added tag #%d ("%s") to journal %d.', $tag->id, $tag->tag, $journal->id));

            return true;
        }
        Log::debug(sprintf('RuleAction AddTag fired but tag %d ("%s") was already added to journal %d.', $tag->id, $tag->tag, $journal->id));

        return false;
    }

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        // journal has this tag maybe?
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser(User::find($journal['user_id']));
        $tag = $factory->findOrCreate($this->action->action_value);
        if (null === $tag) {
            // could not find, could not create tag.
            Log::error(sprintf('RuleAction AddTag. Could not find or create tag "%s"', $this->action->action_value));

            return false;
        }
        $count = DB::table('tag_transaction_journal')
                   ->where('tag_id', $tag->id)
                   ->where('transaction_journal_id', $journal['transaction_journal_id'])
                   ->count();
        if (0 === $count) {
            // add to journal:
            DB::table('tag_transaction_journal')->insert(['tag_id' => $tag->id, 'transaction_journal_id' => $journal['transaction_journal_id']]);
            Log::debug(sprintf('RuleAction AddTag. Added tag #%d ("%s") to journal %d.', $tag->id, $tag->tag, $journal['transaction_journal_id']));

            return true;
        }
        Log::debug(sprintf('RuleAction AddTag fired but tag %d ("%s") was already added to journal %d.', $tag->id, $tag->tag, $journal['transaction_journal_id']));

        return false;
    }
}
