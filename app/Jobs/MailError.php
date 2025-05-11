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
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Class MailError.
 */
class MailError extends Job implements ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * MailError constructor.
     */
    public function __construct(protected array $userData, protected string $destination, protected string $ipAddress, protected array $exception)
    {
        $debug = $this->exception;
        unset($debug['stackTrace'], $debug['headers']);

        app('log')->error(sprintf('Exception is: %s', json_encode($debug)));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email            = (string) config('firefly.site_owner');
        $args             = $this->exception;
        $args['loggedIn'] = $this->userData['id'] > 0;
        $args['user']     = $this->userData;
        $args['ip']       = $this->ipAddress;
        $args['token']    = config('firefly.ipinfo_token');

        // limit number of error mails that can be sent.
        if ($this->reachedLimit()) {
            Log::info('MailError: reached limit, not sending email.');

            return;
        }

        if ($this->attempts() < 3 && '' !== $email) {
            try {
                \Mail::send(
                    ['emails.error-html', 'emails.error-text'],
                    $args,
                    static function (Message $message) use ($email): void {
                        if ('mail@example.com' !== $email) {
                            $message->to($email, $email)->subject((string) trans('email.error_subject'));
                        }
                    }
                );
            } catch (\Exception|TransportException $e) {
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

    private function reachedLimit(): bool
    {
        Log::debug('reachedLimit()');
        $types     = [
            '5m'  => ['limit' => 5, 'reset' => 5 * 60],
            '1h'  => ['limit' => 15, 'reset' => 60 * 60],
            '24h' => ['limit' => 15, 'reset' => 24 * 60 * 60],
        ];
        $file      = storage_path('framework/cache/error-count.json');
        $directory = storage_path('framework/cache');
        $limits    = [];

        if (!is_writable($directory)) {
            Log::error(sprintf('MailError: cannot write to "%s", cannot rate limit errors!', $directory));

            return false;
        }

        if (!file_exists($file)) {
            Log::debug(sprintf('Wrote new file in "%s"', $file));
            file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
        }
        if (file_exists($file)) {
            Log::debug(sprintf('Read file in "%s"', $file));
            $limits = json_decode((string) file_get_contents($file), true);
        }
        // limit reached?
        foreach ($types as $type => $info) {
            Log::debug(sprintf('Now checking limit "%s"', $type), $info);
            if (!array_key_exists($type, $limits)) {
                Log::debug(sprintf('Limit "%s" reset to zero, did not exist yet.', $type));
                $limits[$type] = [
                    'time' => time(),
                    'sent' => 0,
                ];
            }

            if (time() - $limits[$type]['time'] > $info['reset']) {
                Log::debug(sprintf('Time past for this limit is %d seconds, exceeding %d seconds. Reset to zero.', time() - $limits[$type]['time'], $info['reset']));
                $limits[$type] = [
                    'time' => time(),
                    'sent' => 0,
                ];
            }

            if ($limits[$type]['sent'] > $info['limit']) {
                Log::warning(sprintf('Sent %d emails in %s, return true.', $limits[$type]['sent'], $type));

                return true;
            }
            ++$limits[$type]['sent'];
        }
        file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
        Log::debug('No limits reached, return FALSE.');

        return false;
    }
}
