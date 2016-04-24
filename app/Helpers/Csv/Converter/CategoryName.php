<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface;

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
    public function convert(): Category
    {
        /** @var SingleCategoryRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface');

        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $category = $repository->find($this->mapped[$this->index][$this->value]);
        } else {

            $data = [
                'name' => $this->value,
                'user' => Auth::user()->id,
            ];

            $category = $repository->store($data);
        }

        return $category;
    }
}
