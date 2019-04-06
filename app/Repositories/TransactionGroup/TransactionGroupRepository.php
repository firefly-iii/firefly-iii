<?php
/**
 * TransactionGroupRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\TransactionGroup;


use Carbon\Carbon;
use DB;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Update\GroupUpdateService;
use FireflyIII\Support\NullArrayObject;

/**
 * Class TransactionGroupRepository
 */
class TransactionGroupRepository implements TransactionGroupRepositoryInterface
{
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            app('log')->warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * Return object with all found meta field things as Carbon objects.
     *
     * @param int   $journalId
     * @param array $fields
     *
     * @return NullArrayObject
     * @throws Exception
     */
    public function getMetaDateFields(int $journalId, array $fields): NullArrayObject
    {
        $query  = DB
            ::table('journal_meta')
            ->where('transaction_journal_id', $journalId)
            ->whereIn('name', $fields)
            ->whereNull('deleted_at')
            ->get(['name', 'data']);
        $return = [];

        foreach ($query as $row) {
            $return[$row->name] = new Carbon(json_decode($row->data));
        }

        return new NullArrayObject($return);
    }

    /**
     * Return object with all found meta field things.
     *
     * @param int   $journalId
     * @param array $fields
     *
     * @return NullArrayObject
     */
    public function getMetaFields(int $journalId, array $fields): NullArrayObject
    {
        $query  = DB
            ::table('journal_meta')
            ->where('transaction_journal_id', $journalId)
            ->whereIn('name', $fields)
            ->whereNull('deleted_at')
            ->get(['name', 'data']);
        $return = [];

        foreach ($query as $row) {
            $return[$row->name] = json_decode($row->data);
        }

        return new NullArrayObject($return);
    }

    /**
     * Get the note text for a journal (by ID).
     *
     * @param int $journalId
     *
     * @return string|null
     */
    public function getNoteText(int $journalId): ?string
    {
        /** @var Note $note */
        $note = Note
            ::where('noteable_id', $journalId)
            ->where('noteable_type', TransactionJournal::class)
            ->first();
        if (null === $note) {
            return null;
        }

        return $note->text;
    }

    /**
     * Get the tags for a journal (by ID).
     *
     * @param int $journalId
     *
     * @return array
     */
    public function getTags(int $journalId): array
    {
        $result = DB
            ::table('tag_transaction_journal')
            ->leftJoin('tags', 'tag_transaction_journal.tag_id', '=', 'tags.id')
            ->where('tag_transaction_journal.transaction_journal_id', $journalId)
            ->get(['tags.tag']);

        return $result->pluck('tag')->toArray();
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionGroup
     *
     * @throws FireflyException
     */
    public function store(array $data): TransactionGroup
    {
        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * @param TransactionGroup $transactionGroup
     * @param array            $data
     *
     * @return TransactionGroup
     *
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        /** @var GroupUpdateService $service */
        $service      = app(GroupUpdateService::class);
        $updatedGroup = $service->update($transactionGroup, $data);

        return $updatedGroup;
    }
}