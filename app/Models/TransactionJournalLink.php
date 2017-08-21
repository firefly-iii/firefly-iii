<?php
/**
 * TransactionJournalLink.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionJournalLink
 *
 * @package FireflyIII\Models
 */
class TransactionJournalLink extends Model
{
protected $table = 'journal_links';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function linkType()
    {
        return $this->belongsTo(LinkType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function source()
    {
        return $this->belongsTo(TransactionJournal::class,'source_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destination()
    {
        return $this->belongsTo(TransactionJournal::class,'destination_id');
    }


}