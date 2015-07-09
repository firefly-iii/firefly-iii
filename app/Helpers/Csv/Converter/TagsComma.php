<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Tag;
use Illuminate\Support\Collection;

/**
 * Class TagsComma
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class TagsComma extends BasicConverter implements ConverterInterface
{

    /**
     * @return Bill
     */
    public function convert()
    {
        $tags = new Collection;

        $strings = explode(',', $this->value);
        foreach ($strings as $string) {
            $tag = Tag::firstOrCreateEncrypted(
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
