<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;

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
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $value      = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $category   = $repository->find($value);

        return $category;
    }
}
