<?php
/**
 * JournalLinkRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\LinkType;

/**
 * Class JournalLink
 *
 *
 * @package FireflyIII\Http\Requests
 */
class JournalLinkRequest extends Request
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
    public function getLinkInfo(): array
    {
        $return                           = [];
        $linkType                         = $this->get('link_type');
        $parts                            = explode('_', $linkType);
        $return['link_type_id']           = intval($parts[0]);
        $return['transaction_journal_id'] = $this->integer('link_journal_id');
        $return['comments']               = strlen($this->string('comments')) > 0 ? $this->string('comments') : null;
        $return['direction']              = $parts[1];
        if ($return['transaction_journal_id'] === 0 && ctype_digit($this->string('link_other'))) {
            $return['transaction_journal_id'] = $this->integer('link_other');
        }

        return $return;
    }

    /**
     * @return array
     */
    public function rules()
    {
        // all possible combinations of link types and inward / outward:
        $combinations = [];
        $linkTypes    = LinkType::get(['id']);
        /** @var LinkType $type */
        foreach ($linkTypes as $type) {
            $combinations[] = sprintf('%d_inward', $type->id);
            $combinations[] = sprintf('%d_outward', $type->id);
        }
        $string = join(',', $combinations);

        return [
            'link_type'       => sprintf('required|in:%s', $string),
            'link_other'      => 'belongsToUser:transaction_journals',
            'link_journal_id' => 'belongsToUser:transaction_journals',
        ];
    }
}
