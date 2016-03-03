<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Attachment;

use FireflyIII\Models\Attachment;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

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
     * AttachmentRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        Log::debug('Constructed attachment repository for user #' . $user->id . ' (' . $user->email . ')');
        $this->user = $user;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function destroy(Attachment $attachment): bool
    {
        /** @var \FireflyIII\Helpers\Attachments\AttachmentHelperInterface $helper */
        $helper = app('FireflyIII\Helpers\Attachments\AttachmentHelperInterface');

        $file = $helper->getAttachmentLocation($attachment);
        unlink($file);
        $attachment->delete();

        return true;
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
