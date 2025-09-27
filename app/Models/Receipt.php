<?php
declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

final class Receipt extends Model
{
    protected $table = 'receipts';

    protected $fillable = [
        'user_id',
        'receipt_id',
        'merchant',
        'total_amount',
        'currency',
        'purchase_date',
        'vat_amount',
        's3_key',
        'mime',
        'size',
        'transaction_group_id',
    ];
}
