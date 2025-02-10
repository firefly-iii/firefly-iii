<?php

/**
 * TransactionGroupFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;

/**
 * Class TransactionGroupFactory
 */
class TransactionGroupFactory
{
    private TransactionJournalFactory $journalFactory;
    private User                      $user;
    private UserGroup $userGroup;

    /**
     * TransactionGroupFactory constructor.
     */
    public function __construct()
    {
        $this->journalFactory = app(TransactionJournalFactory::class);
    }

    /**
     * Store a new transaction journal.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function create(array $data): TransactionGroup
    {
        app('log')->debug('Now in TransactionGroupFactory::create()');
        $this->journalFactory->setUser($data['user']);
        $this->journalFactory->setUserGroup($data['user_group']);
        $this->journalFactory->setErrorOnHash($data['error_if_duplicate_hash'] ?? false);

        try {
            $collection = $this->journalFactory->create($data);
        } catch (DuplicateTransactionException $e) {
            app('log')->warning('GroupFactory::create() caught journalFactory::create() with a duplicate!');

            throw new DuplicateTransactionException($e->getMessage(), 0, $e);
        }
        $title        = $data['group_title'] ?? null;
        $title        = '' === $title ? null : $title;

        if (null !== $title) {
            $title = substr($title, 0, 1000);
        }
        if (0 === $collection->count()) {
            throw new FireflyException('Created zero transaction journals.');
        }

        $group        = new TransactionGroup();
        $group->user()->associate($this->user);
        $group->userGroup()->associate($data['user_group']);
        $group->title = $title;
        $group->save();

        $group->transactionJournals()->saveMany($collection);

        return $group;
    }

    /**
     * Set the user.
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }
}
