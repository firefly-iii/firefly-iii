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

use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
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
            'link_type_id'   => 'exists:link_types,id',
            'link_type_name' => 'exists:link_types,name',
            'inward_id'      => 'belongsToUser:transaction_journals,id|different:outward_id',
            'outward_id'     => 'belongsToUser:transaction_journals,id|different:inward_id',
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
                $this->validateUpdate($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }

    private function validateUpdate(Validator $validator): void
    {
        /** @var TransactionJournalLink $existing */
        $existing     = $this->route()->parameter('journalLink');
        $data         = $validator->getData();

        /** @var LinkTypeRepositoryInterface $repository */
        $repository   = app(LinkTypeRepositoryInterface::class);
        $repository->setUser(auth()->user());

        /** @var JournalRepositoryInterface $journalRepos */
        $journalRepos = app(JournalRepositoryInterface::class);
        $journalRepos->setUser(auth()->user());

        $inwardId     = $data['inward_id'] ?? $existing->source_id;
        $outwardId    = $data['outward_id'] ?? $existing->destination_id;
        $inward       = $journalRepos->find((int) $inwardId);
        $outward      = $journalRepos->find((int) $outwardId);
        if (null === $inward) {
            $inward = $existing->source;
        }
        if (null === $outward) {
            $outward = $existing->destination;
        }
        if ($inward->id === $outward->id) {
            $validator->errors()->add('inward_id', 'Inward ID must be different from outward ID.');
            $validator->errors()->add('outward_id', 'Inward ID must be different from outward ID.');
        }

        $inDB         = $repository->findSpecificLink($existing->linkType, $inward, $outward);
        if (null === $inDB) {
            return;
        }
        if ($inDB->id !== $existing->id) {
            $validator->errors()->add('outward_id', 'Already have a link between inward and outward.');
            $validator->errors()->add('inward_id', 'Already have a link between inward and outward.');
        }
    }
}
