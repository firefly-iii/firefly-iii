<?php
/**
 * LinkTypeRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\LinkType;

use FireflyIII\Models\LinkType;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class LinkTypeRepository
 *
 * @package FireflyIII\Repositories\LinkType
 */
class LinkTypeRepository implements LinkTypeRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param LinkType $linkType
     *
     * @return int
     */
    public function countJournals(LinkType $linkType): int
    {
        return $linkType->transactionJournalLinks()->count() * 2;
    }

    /**
     * @param LinkType $linkType
     * @param LinkType $moveTo
     *
     * @return bool
     */
    public function destroy(LinkType $linkType, LinkType $moveTo): bool
    {
        if (!is_null($moveTo->id)) {
            TransactionJournalLink::where('link_type_id', $linkType->id)->update(['link_type_id' => $moveTo->id]);
        }
        $linkType->delete();
        return true;
    }

    /**
     * @param int $id
     *
     * @return LinkType
     */
    public function find(int $id): LinkType
    {
        $linkType = LinkType::find($id);
        if (is_null($linkType)) {
            return new LinkType;
        }

        return $linkType;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return LinkType::orderBy('name', 'ASC')->get();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return LinkType
     */
    public function store(array $data): LinkType
    {
        $linkType           = new LinkType;
        $linkType->name     = $data['name'];
        $linkType->inward   = $data['inward'];
        $linkType->outward  = $data['outward'];
        $linkType->editable = true;
        $linkType->save();

        return $linkType;
    }

    /**
     * @param LinkType $linkType
     * @param array    $data
     *
     * @return LinkType
     */
    public function update(LinkType $linkType, array $data): LinkType
    {
        $linkType->name    = $data['name'];
        $linkType->inward  = $data['inward'];
        $linkType->outward = $data['outward'];
        $linkType->save();

        return $linkType;

    }
}