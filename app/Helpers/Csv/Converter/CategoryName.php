<?php
/**
 * CategoryName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;

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
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);

        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $category = $repository->find($this->mapped[$this->index][$this->value]);

            return $category;
        }

        $data = [
            'name' => $this->value,
            'user' => Auth::user()->id,
        ];

        $category = $repository->store($data);

        return $category;
    }
}
