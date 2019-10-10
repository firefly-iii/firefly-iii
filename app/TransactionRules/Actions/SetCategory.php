<?php
/**
 * SetCategory.php
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

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class SetCategory.
 */
class SetCategory implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

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
     * Set category X
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $name = $this->action->action_value;

        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($journal->user);
        $category = $factory->findOrCreate(null, $name);
        if (null === $category) {
            Log::error(sprintf('Action SetCategory did not fire because "%s" did not result in a valid category.', $name));

            return false;
        }

        $journal->categories()->sync([$category->id]);

        Log::debug(sprintf('RuleAction SetCategory set the category of journal #%d to category #%d ("%s").', $journal->id, $category->id, $category->name));

        return true;
    }
}
