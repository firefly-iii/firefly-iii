<?php
/**
 * AttachmentFactory.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use FireflyIII\User;
use Log;

/**
 * Class AttachmentFactory
 */
class AttachmentFactory
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }
    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Attachment|null
     */
    public function create(array $data): ?Attachment
    {
        // create attachment:
        $attachment = Attachment::create(
            [
                'user_id'         => $this->user->id,
                'attachable_id'   => $data['model_id'],
                'attachable_type' => $data['model'],
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
