<?php
/**
 * MailError.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Jobs;

use ErrorException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Mail;
use Swift_TransportException;

/**
 * Class MailError.
 */
class MailError extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /** @var string */
    protected $destination;
    /** @var array */
    protected $exception;
    /** @var string */
    protected $ipAddress;
    /** @var array */
    protected $userData;

    /**
     * MailError constructor.
     *
     * @param array  $userData
     * @param string $destination
     * @param string $ipAddress
     * @param array  $exceptionData
     */
    public function __construct(array $userData, string $destination, string $ipAddress, array $exceptionData)
    {
        $this->userData    = $userData;
        $this->destination = $destination;
        $this->ipAddress   = $ipAddress;
        $this->exception   = $exceptionData;
        $debug             = $exceptionData;
        unset($debug['stackTrace']);
        Log::error('Exception is: ' . json_encode($debug));
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->attempts() < 3) {
            // mail?
            try {
                $email            = env('SITE_OWNER');
                $args             = $this->exception;
                $args['loggedIn'] = $this->userData['id'] > 0;
                $args['user']     = $this->userData;
                $args['ip']       = $this->ipAddress;

                Mail::send(
                    ['emails.error-html', 'emails.error-text'],
                    $args,
                    function (Message $message) use ($email) {
                        if ('mail@example.com' !== $email) {
                            $message->to($email, $email)->subject('Caught an error in Firely III');
                        }
                    }
                );
            } catch (Swift_TransportException $e) {
                // could also not mail! :o
                Log::error('Swift Transport Exception' . $e->getMessage());
            } catch (ErrorException $e) {
                Log::error('ErrorException ' . $e->getMessage());
            }
        }
    }
}
