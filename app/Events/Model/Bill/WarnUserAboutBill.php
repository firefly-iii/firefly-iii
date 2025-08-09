<?php


declare(strict_types=1);

namespace FireflyIII\Events\Model\Bill;

use FireflyIII\Events\Event;
use FireflyIII\Models\Bill;
use Illuminate\Queue\SerializesModels;

/**
 * Class WarnUserAboutBill.
 */
class WarnUserAboutBill extends Event
{
    use SerializesModels;

    public function __construct(public Bill $bill, public string $field, public int $diff) {}
}
