<?php
/**
 * MassEditJournalRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

/**
 * Class MassEditJournalRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class MassEditJournalRequest extends Request
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
    public function rules()
    {
        return [
            'description.*'            => 'required|min:1,max:255',
            'source_account_id.*'      => 'numeric|belongsToUser:accounts,id',
            'destination_account_id.*' => 'numeric|belongsToUser:accounts,id',
            'revenue_account'          => 'max:255',
            'expense_account'          => 'max:255',
        ];
    }
}
