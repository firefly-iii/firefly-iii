<?php
/**
 * ReportNewJournalsMail.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Mail;

use FireflyIII\Models\TransactionGroup;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Class ReportNewJournalsMail.
 *
 * Sends a list of newly created journals to the user.
 *
 * @codeCoverageIgnore
 */
class ReportNewJournalsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string     $email;
    public Collection $groups;
    public string     $ipAddress;
    public array      $transformed;

    /**
     * ConfirmEmailChangeMail constructor.
     *
     * @param string     $email
     * @param string     $ipAddress
     * @param Collection $groups
     */
    public function __construct(string $email, string $ipAddress, Collection $groups)
    {
        $this->email     = $email;
        $this->ipAddress = $ipAddress;
        $this->groups    = $groups;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $this->transform();

        return $this->view('emails.report-new-journals-html')->text('emails.report-new-journals-text')
                    ->subject((string)trans_choice('email.new_journals_subject', $this->groups->count()));
    }

    private function transform(): void
    {
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);

        /** @var TransactionGroup $group */
        foreach ($this->groups as $group) {
            $this->transformed[] = $transformer->transformObject($group);
        }
    }
}
