<?php

namespace FireflyIII\Repositories\Tag;


use Auth;
use FireflyIII\Models\Tag;
use Illuminate\Support\Collection;

/**
 * Class TagRepository
 *
 * @package FireflyIII\Repositories\Tag
 */
class TagRepository implements TagRepositoryInterface
{

    /**
     * @return Collection
     */
    public function get()
    {
        /** @var Collection $tags */
        $tags = Auth::user()->tags()->get();
        $tags->sortBy(
            function (Tag $tag) {
                return $tag->tag;
            }
        );

        return $tags;
    }

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data)
    {
        $tag              = new Tag;
        $tag->tag         = $data['tag'];
        $tag->date        = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude    = $data['latitude'];
        $tag->longitude   = $data['longitude'];
        $tag->zoomLevel   = $data['zoomLevel'];
        $tag->tagMode     = $data['tagMode'];
        $tag->user()->associate(Auth::user());
        $tag->save();

        return $tag;


    }

    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data) {
        $tag->tag = $data['tag'];
        $tag->date = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude = $data['latitude'];
        $tag->longitude = $data['longitude'];
        $tag->zoomLevel = $data['zoomLevel'];
        $tag->tagMode = $data['tagMode'];
        $tag->save();
        return $tag;
    }
}