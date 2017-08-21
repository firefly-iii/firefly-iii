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


use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public function destination()
    {
        return $this->belongsTo(TransactionJournal::class, 'destination_id');
    }

    /**
     * @param $value
     *
     * @return null|string
     */
    public function getCommentAttribute($value): ?string
    {
        if (!is_null($value)) {
            return Crypt::decrypt($value);
        }

        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function linkType(): BelongsTo
    {
        return $this->belongsTo(LinkType::class);
    }

    /**
     *
     * @param $value
     */
    public function setCommentAttribute($value): void
    {
        if (!is_null($value) && strlen($value) > 0) {
            $this->attributes['comment'] = Crypt::encrypt($value);

            return;
        }
        $this->attributes['comment'] = null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function source()
    {
        return $this->belongsTo(TransactionJournal::class, 'source_id');
    }


}