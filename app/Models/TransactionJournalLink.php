<?php
/**
 * TransactionJournalLink.php
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
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionJournalLink.
 *
 * @property int                $id
 * @property Carbon             $created_at
 * @property Carbon             $updated_at
 * @property string             $comment
 * @property TransactionJournal $source
 * @property TransactionJournal $destination
 * @property LinkType           $linkType
 * @property int                $link_type_id
 * @property int                $source_id
 * @property int                $destination_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Note[] $notes
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereDestinationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereLinkTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournalLink whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TransactionJournalLink extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    /** @var string The table to store the data in */
    protected $table = 'journal_links';

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): TransactionJournalLink
    {
        if (auth()->check()) {
            $linkId = (int)$value;
            $link   = self::where('journal_links.id', $linkId)
                          ->leftJoin('transaction_journals as t_a', 't_a.id', '=', 'source_id')
                          ->leftJoin('transaction_journals as t_b', 't_b.id', '=', 'destination_id')
                          ->where('t_a.user_id', auth()->user()->id)
                          ->where('t_b.user_id', auth()->user()->id)
                          ->first(['journal_links.*']);
            if (null !== $link) {
                return $link;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class, 'destination_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function linkType(): BelongsTo
    {
        return $this->belongsTo(LinkType::class);
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class, 'source_id');
    }
}
