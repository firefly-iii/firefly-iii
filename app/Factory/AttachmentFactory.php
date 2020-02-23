<?php
/**
 * AttachmentFactory.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Log;

/**
 * Class AttachmentFactory
 */
class AttachmentFactory
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param array $data
     *
     * @return Attachment|null
     * @throws FireflyException
     */
    public function create(array $data): ?Attachment
    {
        // append if necessary.
        $model = false === strpos($data['model'], 'FireflyIII') ? sprintf('FireflyIII\\Models\\%s', $data['model']) : $data['model'];

        // get journal instead of transaction.
        if (Transaction::class === $model) {
            /** @var Transaction $transaction */
            $transaction = $this->user->transactions()->find((int)$data['model_id']);
            if (null === $transaction) {
                throw new FireflyException('Unexpectedly could not find transaction'); // @codeCoverageIgnore
            }
            $data['model_id'] = $transaction->transaction_journal_id;
            $model            = TransactionJournal::class;
        }

        // create attachment:
        $attachment = Attachment::create(
            [
                'user_id'         => $this->user->id,
                'attachable_id'   => $data['model_id'],
                'attachable_type' => $model,
                'md5'             => '',
                'filename'        => $data['filename'],
                'title'           => '' === $data['title'] ? null : $data['title'],
                'description'     => null,
                'mime'            => '',
                'size'            => 0,
                'uploaded'        => 0,
            ]
        );
        $notes      = (string)($data['notes'] ?? '');
        if ('' !== $notes) {
            $note = new Note;
            $note->noteable()->associate($attachment);
            $note->text = $notes;
            $note->save();
        }

        return $attachment;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
