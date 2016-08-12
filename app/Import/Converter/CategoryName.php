<?php
/**
 * CategoryName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Log;

/**
 * Class CategoryName
 *
 * @package FireflyIII\Import\Converter
 */
class CategoryName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Category
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using CategoryName', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);
            return new Category;
        }

        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class, [$this->user]);

        if (isset($this->mapping[$value])) {
            Log::debug('Found category in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $category = $repository->find(intval($this->mapping[$value]));
            if (!is_null($category->id)) {
                Log::debug('Found category by ID', ['id' => $category->id]);
                $this->setCertainty(100);
                return $category;
            }
        }

        // not mapped? Still try to find it first:
        $category = $repository->findByName($value);
        if (!is_null($category->id)) {
            Log::debug('Found category by name ', ['id' => $category->id]);
            $this->setCertainty(100);
            return $category;
        }

        // create new category. Use a lot of made up values.
        $category = $repository->store(
            [
                'name'    => $value,
                'user' => $this->user->id,
            ]
        );
        $this->setCertainty(100);

        return $category;

    }
}
