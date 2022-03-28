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
    public string $ipAddress;

    /**
     * ConfirmEmailChangeMail constructor.
     *
     * @param Bill   $bill
     * @param string $field
     * @param int    $diff
     * @param string $ipAddress
     */
    public function __construct(Bill $bill, string $field, int $diff, string $ipAddress)
    {
        $this->bill      = $bill;
        $this->field     = $field;
        $this->diff      = $diff;
        $this->ipAddress = $ipAddress;
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
            ->view('emails.bill-warning-html')
            ->text('emails.bill-warning-text')
            ->subject($subject);
    }
}
