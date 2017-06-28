<?php
/**
 * AttachmentRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Attachment;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Storage;

/**
 * Class AttachmentRepository
 *
 * @package FireflyIII\Repositories\Attachment
 */
class AttachmentRepository implements AttachmentRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function destroy(Attachment $attachment): bool
    {
        /** @var AttachmentHelperInterface $helper */
        $helper = app(AttachmentHelperInterface::class);

        $file = $helper->getAttachmentLocation($attachment);
        unlink($file);
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
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBetween(Carbon $start, Carbon $end): Collection
    {
        $query = $this->user
            ->attachments()
            ->leftJoin('transaction_journals', 'attachments.attachable_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
            ->get(['attachments.*']);

        return $query;
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
            $content = Crypt::decrypt($disk->get($file));
        }
        if (is_bool($content)) {
            Log::error(sprintf('Attachment #%d may be corrupted: the content could not be decrypted.', $attachment->id));

            return '';
        }

        return $content;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Attachment $attachment
     * @param array      $data
     *
     * @return Attachment
     */
    public function update(Attachment $attachment, array $data): Attachment
    {

        $attachment->title       = $data['title'];
        $attachment->description = $data['description'];
        $attachment->notes       = $data['notes'];
        $attachment->save();

        return $attachment;

    }
}
