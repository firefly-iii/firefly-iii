<?php

namespace FireflyIII\Repositories\Tag;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;


/**
 * Interface TagRepositoryInterface
 *
 * @package FireflyIII\Repositories\Tag
 */
interface TagRepositoryInterface {

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data);

    /**
     * @return Collection
     */
    public function get();

    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data);

    /**
     * @param Tag   $tag
     *
     * @return boolean
     */
    public function destroy(Tag $tag);

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return boolean
     */
    public function connect(TransactionJournal $journal, Tag $tag);
}