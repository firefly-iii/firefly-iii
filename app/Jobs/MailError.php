<?php
/**
 * MailError.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * Class MailError
 *
 * @package FireflyIII\Jobs
 */
class MailError extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /** @var  string */
    protected $destination;
    /** @var  array */
    protected $exception;
    /** @var  string */
    protected $ipAddress;
    /** @var  array */
    protected $userData;

    /**
     * MailError constructor.
     *
     * @param array  $userData
     * @param string $destination
     * @param string $ipAddress
     * @param array  $exceptionData
     *
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
     *
     * @return void
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
                    ['emails.error-html', 'emails.error-text'], $args,
                    function (Message $message) use ($email) {
                        if ($email !== 'mail@example.com') {
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
