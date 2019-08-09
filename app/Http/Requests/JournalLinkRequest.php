<?php
/**
 * JournalLinkRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
/** @noinspection PhpDynamicAsStaticMethodCallInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\LinkType;

/**
 * Class JournalLink.
 */
class JournalLinkRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Returns the data required by the controller.
     *
     * @return array
     */
    public function getLinkInfo(): array
    {
        $return                           = [];
        $linkType                         = $this->get('link_type');
        $parts                            = explode('_', $linkType);
        $return['link_type_id']           = (int)$parts[0];
        $return['transaction_journal_id'] = $this->integer('opposing');
        $return['notes']                  = $this->string('notes');
        $return['direction']              = $parts[1];

        return $return;
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        // all possible combinations of link types and inward / outward:
        $combinations = [];
        $linkTypes    = LinkType::get(['id']);
        /** @var LinkType $type */
        foreach ($linkTypes as $type) {
            $combinations[] = sprintf('%d_inward', $type->id);
            $combinations[] = sprintf('%d_outward', $type->id);
        }
        $string = implode(',', $combinations);

        // fixed
        return [
            'link_type' => sprintf('required|in:%s', $string),
            'opposing'  => 'belongsToUser:transaction_journals',
        ];
    }
}
