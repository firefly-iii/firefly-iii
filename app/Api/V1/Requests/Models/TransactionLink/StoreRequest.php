<?php

/**
 * TransactionLinkRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Models\TransactionLink;

use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        return [
            'link_type_id'   => $this->convertInteger('link_type_id'),
            'link_type_name' => $this->convertString('link_type_name'),
            'inward_id'      => $this->convertInteger('inward_id'),
            'outward_id'     => $this->convertInteger('outward_id'),
            'notes'          => $this->stringWithNewlines('notes'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'link_type_id'   => 'exists:link_types,id|required_without:link_type_name',
            'link_type_name' => 'exists:link_types,name|required_without:link_type_id',
            'inward_id'      => 'required|belongsToUser:transaction_journals,id|different:outward_id',
            'outward_id'     => 'required|belongsToUser:transaction_journals,id|different:inward_id',
            'notes'          => 'min:1|max:32768|nullable',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                $this->validateExistingLink($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }

    private function validateExistingLink(Validator $validator): void
    {
        /** @var User $user */
        $user         = auth()->user();

        /** @var LinkTypeRepositoryInterface $repository */
        $repository   = app(LinkTypeRepositoryInterface::class);
        $repository->setUser($user);

        /** @var JournalRepositoryInterface $journalRepos */
        $journalRepos = app(JournalRepositoryInterface::class);
        $journalRepos->setUser($user);

        $data         = $validator->getData();
        $inwardId     = (int) ($data['inward_id'] ?? 0);
        $outwardId    = (int) ($data['outward_id'] ?? 0);
        $inward       = $journalRepos->find($inwardId);
        $outward      = $journalRepos->find($outwardId);

        if (null === $inward) {
            $validator->errors()->add('inward_id', 'Invalid inward ID.');

            return;
        }
        if (null === $outward) {
            $validator->errors()->add('outward_id', 'Invalid outward ID.');

            return;
        }

        if ($repository->findLink($inward, $outward)) {
            // only if not updating:
            $link = $this->route()->parameter('journalLink');
            if (null === $link) {
                $validator->errors()->add('outward_id', 'Already have a link between inward and outward.');
                $validator->errors()->add('inward_id', 'Already have a link between inward and outward.');
            }
        }
    }
}
