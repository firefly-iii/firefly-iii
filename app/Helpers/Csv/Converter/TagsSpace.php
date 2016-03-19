<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Tag;
use Illuminate\Support\Collection;

/**
 * Class TagsSpace
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class TagsSpace extends BasicConverter implements ConverterInterface
{

    /**
     * @return Collection
     */
    public function convert()
    {
        $tags = new Collection;

        $strings = explode(' ', $this->value);
        foreach ($strings as $string) {
            $tag = Tag::firstOrCreateEncrypted( // See issue #180
                [
                    'tag'     => $string,
                    'tagMode' => 'nothing',
                    'user_id' => Auth::user()->id,
                ]
            );
            $tags->push($tag);
        }
        $tags = $tags->merge($this->data['tags']);

        return $tags;
    }
}
