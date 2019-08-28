<?php
/**
 * AttachmentRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Attachment;

use Crypt;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AttachmentFactory;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use FireflyIII\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Log;

/**
 * Class AttachmentRepository.
 *
 */
class AttachmentRepository implements AttachmentRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     * @throws \Exception
     */
    public function destroy(Attachment $attachment): bool
    {
        /** @var AttachmentHelperInterface $helper */
        $helper = app(AttachmentHelperInterface::class);

        $path = $helper->getAttachmentLocation($attachment);
        try {
            Storage::disk('upload')->delete($path);
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete file for attachment %d: %s', $attachment->id, $e->getMessage()));
        }
        $attachment->delete();

        return true;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function exists(Attachment $attachment): bool
    {
        /** @var Storage $disk */
        $disk = Storage::disk('upload');

        return $disk->exists($attachment->fileName());
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->user->attachments()->get();
    }

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getContent(Attachment $attachment): string
    {
        // create a disk.
        $disk    = Storage::disk('upload');
        $file    = $attachment->fileName();
        $content = '';

        if ($disk->exists($file)) {
            try {
                $content = Crypt::decrypt($disk->get($file));
            } catch (FileNotFoundException $e) {
                Log::debug(sprintf('File not found: %e', $e->getMessage()));
                $content = false;
            }
        }
        if (\is_bool($content)) {
            Log::error(sprintf('Attachment #%d may be corrupted: the content could not be decrypted.', $attachment->id));

            return '';
        }

        return $content;
    }

    /**
     * Get attachment note text or empty string.
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getNoteText(Attachment $attachment): ?string
    {
        $note = $attachment->notes()->first();
        if (null !== $note) {
            return (string)$note->text;
        }

        return null;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Attachment
     * @throws FireflyException
     */
    public function store(array $data): Attachment
    {
        /** @var AttachmentFactory $factory */
        $factory = app(AttachmentFactory::class);
        $factory->setUser($this->user);
        $result = $factory->create($data);
        if (null === $result) {
            throw new FireflyException('Could not store attachment.');
        }

        return $result;
    }

    /**
     * @param Attachment $attachment
     * @param array      $data
     *
     * @return Attachment
     */
    public function update(Attachment $attachment, array $data): Attachment
    {
        $attachment->title = $data['title'];

        // update filename, if present and different:
        if (isset($data['filename']) && '' !== $data['filename'] && $data['filename'] !== $attachment->filename) {
            $attachment->filename = $data['filename'];
        }
        $attachment->save();
        $this->updateNote($attachment, $data['notes'] ?? '');

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     * @param string     $note
     *
     * @return bool
     * @throws Exception
     */
    public function updateNote(Attachment $attachment, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $attachment->notes()->first();
            if (null !== $dbNote) {
                try {
                    $dbNote->delete();
                } catch (Exception $e) {
                    Log::debug(sprintf('Could not delete note: %s', $e->getMessage()));
                }
            }

            return true;
        }
        $dbNote = $attachment->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($attachment);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
