<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class TagsComma
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class TagsComma extends BasicConverter implements ConverterInterface
{

    /**
     * @return Collection
     */
    public function convert()
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Tag\TagRepositoryInterface');
        $tags       = new Collection;

        $strings = explode(',', $this->value);
        foreach ($strings as $string) {
            $data = [
                'tag'         => $string,
                'date'        => null,
                'description' => null,
                'latitude'    => null,
                'longitude'   => null,
                'zoomLevel'   => null,
                'tagMode'     => 'nothing',
            ];
            $tag  = $repository->store($data); // should validate first?
            $tags->push($tag);
        }
        $tags = $tags->merge($this->data['tags']);

        return $tags;
    }
}
