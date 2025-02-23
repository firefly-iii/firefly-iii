<?php

/**
 * AttachmentRepository.php
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

namespace FireflyIII\Repositories\Attachment;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AttachmentFactory;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;

/**
 * Class AttachmentRepository.
 */
class AttachmentRepository implements AttachmentRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    /**
     * @throws \Exception
     */
    public function destroy(Attachment $attachment): bool
    {
        /** @var AttachmentHelperInterface $helper */
        $helper = app(AttachmentHelperInterface::class);

        $path   = $helper->getAttachmentLocation($attachment);

        try {
            Storage::disk('upload')->delete($path);
        } catch (UnableToDeleteFile $e) {
            // @ignoreException
        }
        $attachment->delete();

        return true;
    }

    public function getContent(Attachment $attachment): string
    {
        // create a disk.
        $disk               = Storage::disk('upload');
        $file               = $attachment->fileName();
        $unencryptedContent = '';

        if ($disk->exists($file)) {
            $encryptedContent = (string) $disk->get($file);

            try {
                $unencryptedContent = \Crypt::decrypt($encryptedContent); // verified
            } catch (DecryptException $e) {
                app('log')->debug(sprintf('Could not decrypt attachment #%d but this is fine: %s', $attachment->id, $e->getMessage()));
                $unencryptedContent = $encryptedContent;
            }
        }

        return $unencryptedContent;
    }

    public function exists(Attachment $attachment): bool
    {
        /** @var Storage $disk */
        $disk = Storage::disk('upload');

        return $disk->exists($attachment->fileName());
    }

    public function get(): Collection
    {
        return $this->user->attachments()->get();
    }

    /**
     * Get attachment note text or empty string.
     */
    public function getNoteText(Attachment $attachment): ?string
    {
        $note = $attachment->notes()->first();
        if (null !== $note) {
            return (string) $note->text;
        }

        return null;
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Attachment
    {
        /** @var AttachmentFactory $factory */
        $factory = app(AttachmentFactory::class);
        $factory->setUser($this->user);
        $result  = $factory->create($data);
        if (null === $result) {
            throw new FireflyException('Could not store attachment.');
        }

        return $result;
    }

    public function update(Attachment $attachment, array $data): Attachment
    {
        if (array_key_exists('title', $data)) {
            $attachment->title = $data['title'];
        }

        if (array_key_exists('filename', $data) && '' !== (string) $data['filename'] && $data['filename'] !== $attachment->filename) {
            $attachment->filename = $data['filename'];
        }
        // update model (move attachment)
        // should be validated already:
        if (array_key_exists('attachable_type', $data) && array_key_exists('attachable_id', $data)) {
            $attachment->attachable_id   = (int) $data['attachable_id'];
            $attachment->attachable_type = sprintf('FireflyIII\Models\%s', $data['attachable_type']);
        }

        $attachment->save();
        $attachment->refresh();
        if (array_key_exists('notes', $data)) {
            $this->updateNote($attachment, (string) $data['notes']);
        }

        return $attachment;
    }

    public function updateNote(Attachment $attachment, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $attachment->notes()->first();
            if (null !== $dbNote) {
                try {
                    $dbNote->delete();
                } catch (\LogicException $e) {
                    app('log')->error($e->getMessage());
                }
            }

            return true;
        }
        $dbNote       = $attachment->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($attachment);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
