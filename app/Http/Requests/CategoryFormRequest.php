<?php
/**
 * CategoryFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\Category\CategoryRepositoryInterface;

/**
 * Class CategoryFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class CategoryFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getCategoryData(): array
    {
        return [
            'name' => $this->string('name'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $nameRule   = 'required|between:1,100|uniqueObjectForUser:categories,name';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $nameRule = 'required|between:1,100|uniqueObjectForUser:categories,name,' . intval($this->get('id'));
        }
        // fixed
        return [
            'name' => $nameRule,
        ];
    }
}
