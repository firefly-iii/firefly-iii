<?php
/**
 * CategoryId.php
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
 * Class CategoryId
 *
 * @package FireflyIII\Import\Converter
 */
class CategoryId extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Category
     */
    public function convert($value)
    {
        $value = intval(trim($value));
        Log::debug('Going to convert using CategoryId', ['value' => $value]);

        if ($value === 0) {
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
        $category = $repository->find($value);
        if (!is_null($category->id)) {
            Log::debug('Found category by ID ', ['id' => $category->id]);
            $this->setCertainty(100);
            return $category;
        }

        // should not really happen. If the ID does not match FF, what is FF supposed to do?
        $this->setCertainty(0);

        Log::info(sprintf('Could not find category with ID %d. Will return NULL', $value));

        return new Category;

    }
}
