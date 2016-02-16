<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Category;

/**
 * Class CategoryName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CategoryName extends BasicConverter implements ConverterInterface
{

    /**
     * @return Category
     */
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $category = Auth::user()->categories()->find($this->mapped[$this->index][$this->value]);
        } else {
            $category = Category::firstOrCreateEncrypted( // See issue #180
                [
                    'name'    => $this->value,
                    'user_id' => Auth::user()->id,
                ]
            );
        }

        return $category;
    }
}
