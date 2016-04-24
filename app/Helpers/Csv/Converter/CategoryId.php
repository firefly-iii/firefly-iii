<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface;

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
    public function convert(): Category
    {
        /** @var SingleCategoryRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface');

        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $category = $repository->find($this->mapped[$this->index][$this->value]);
        } else {
            $category = $repository->find($this->value);
        }

        return $category;
    }
}
