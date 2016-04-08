<?php

namespace FireflyIII\Jobs;

use ErrorException;
use FireflyIII\User;
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
    /** @var  User */
    protected $user;

    /**
     * MailError constructor.
     *
     * @param User   $user
     * @param string $destination
     * @param string $ipAddress
     * @param array  $exceptionData
     *
     */
    public function __construct(User $user, string $destination, string $ipAddress, array $exceptionData)
    {
        $this->user        = $user;
        $this->destination = $destination;
        $this->ipAddress   = $ipAddress;
        $this->exception   = $exceptionData;

        Log::debug('In mail job constructor for error handler.');
        Log::error('Exception is: ' . json_encode($exceptionData));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('Start of handle()');
        if ($this->attempts() < 3) {
            // mail?
            try {
                $email            = env('SITE_OWNER');
                $args             = $this->exception;
                $args['loggedIn'] = !is_null($this->user->id);
                $args['user']     = $this->user;
                $args['ip']       = $this->ipAddress;

                Mail::send(
                    ['emails.error-html', 'emails.error'], $args,
                    function (Message $message) use ($email) {
                        if ($email != 'mail@example.com') {
                            $message->to($email, $email)->subject('Caught an error in Firely III.');
                        }
                    }
                );
            } catch (Swift_TransportException $e) {
                // could also not mail! :o
                Log::error('Swift Transport Exception' . $e->getMessage());
            } catch (ErrorException $e) {
                Log::error('ErrorException ' . $e->getMessage());
            }
            Log::debug('Successfully handled error.');
        }
    }
}
