<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\TransactionGroup;

use FireflyIII\Events\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class TransactionGroupRequestsAuditLogEntry extends Event
{
    use SerializesModels;

    public function __construct(
        public Model $changer,
        public Model $auditable,
        public string $field,
        public mixed $before,
        public mixed $after
    ) {}
}
