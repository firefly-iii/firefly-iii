<?php

namespace FireflyIII\Mail;

use FireflyIII\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BillWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public Bill   $bill;
    public string $field;
    public int    $diff;

    /**
     * ConfirmEmailChangeMail constructor.
     *
     * @param Bill   $bill
     * @param string $field
     * @param int    $diff
     */
    public function __construct(Bill $bill, string $field, int $diff)
    {
        $this->bill      = $bill;
        $this->field     = $field;
        $this->diff      = $diff;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $subject = (string) trans(sprintf('email.bill_warning_subject_%s', $this->field), ['diff' => $this->diff, 'name' => $this->bill->name]);
        if (0 === $this->diff) {
            $subject = (string) trans(sprintf('email.bill_warning_subject_now_%s', $this->field), ['diff' => $this->diff, 'name' => $this->bill->name]);
        }

        return $this
            ->markdown('emails.bill-warning')
            ->subject($subject);
    }
}
