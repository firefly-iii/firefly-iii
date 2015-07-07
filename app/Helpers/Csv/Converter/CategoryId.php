<?php
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Category;

/**
 * Class CategoryId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CategoryId extends BasicConverter implements ConverterInterface
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
            $category = Auth::user()->categories()->find($this->value);
        }

        return $category;
    }
}