<?php

/**
 * MailError.php
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

namespace FireflyIII\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Class MailError.
 */
class MailError extends Job implements ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    protected string $destination;
    protected array  $exception;
    protected string $ipAddress;
    protected array  $userData;

    /**
     * MailError constructor.
     */
    public function __construct(array $userData, string $destination, string $ipAddress, array $exceptionData)
    {
        $this->userData    = $userData;
        $this->destination = $destination;
        $this->ipAddress   = $ipAddress;
        $this->exception   = $exceptionData;
        $debug             = $exceptionData;
        unset($debug['stackTrace'], $debug['headers']);

        app('log')->error(sprintf('Exception is: %s', json_encode($debug)));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email            = (string)config('firefly.site_owner');
        $args             = $this->exception;
        $args['loggedIn'] = $this->userData['id'] > 0;
        $args['user']     = $this->userData;
        $args['ip']       = $this->ipAddress;
        $args['token']    = config('firefly.ipinfo_token');
        if ($this->attempts() < 3 && '' !== $email) {
            try {
                \Mail::send(
                    ['emails.error-html', 'emails.error-text'],
                    $args,
                    static function (Message $message) use ($email): void {
                        if ('mail@example.com' !== $email) {
                            $message->to($email, $email)->subject((string)trans('email.error_subject'));
                        }
                    }
                );
            } catch (\Exception|TransportException $e) { // @phpstan-ignore-line
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    app('log')->warning('[Bcc] Could not email or log the error. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    app('log')->warning('[RFC] Could not email or log the error. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
            }
        }
    }
}
