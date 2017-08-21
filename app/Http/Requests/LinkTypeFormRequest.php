<?php
/**
 * LinkTypeFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;

/**
 * Class BillFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class LinkTypeFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged and admins
        return auth()->check() && auth()->user()->hasRole('owner');
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var LinkTypeRepositoryInterface $repository */
        $repository = app(LinkTypeRepositoryInterface::class);
        $nameRule   = 'required|min:1|unique:link_types,name';
        $idRule     = '';
        if (!is_null($repository->find($this->integer('id'))->id)) {
            $idRule   = 'exists:link_types,id';
            $nameRule = 'required|min:1';
        }

        $rules = [
            'id'      => $idRule,
            'name'    => $nameRule,
            'inward'  => 'required|min:1|different:outward',
            'outward' => 'required|min:1|different:inward',
        ];

        return $rules;
    }
}
