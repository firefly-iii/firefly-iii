<?php
namespace FireflyIII\Shared\Mail;
use Swift_RfcComplianceException;

/**
 * Class Registration
 *
 * @package FireflyIII\Shared\Mail
 */
class Registration implements RegistrationInterface
{
    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function sendPasswordMail(\User $user)
    {

        $password       = \Str::random(12);
        $user->password = $password;
        $user->reset    = \Str::random(32); // new one.
        $user->forceSave();
        $email = $user->email;


        $data = ['password' => $password];
        try {
            \Mail::send(
                ['emails.user.register-html', 'emails.user.register-text'], $data, function ($message) use ($email) {
                    $message->to($email, $email)->subject('Welcome to Firefly!');
                }
            );
        } catch(Swift_RfcComplianceException $e) {}
    }

    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function sendResetVerification(\User $user)
    {
        $reset       = \Str::random(32);
        $user->reset = $reset;
        $user->forceSave();
        $email = $user->email;

        $data = ['reset' => $reset];
        \Mail::send(
            ['emails.user.remindme-html', 'emails.user.remindme-text'], $data, function ($message) use ($email) {
                $message->to($email, $email)->subject('Forgot your password?');
            }
        );


    }

    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function sendVerificationMail(\User $user)
    {

        $reset       = \Str::random(32);
        $user->reset = $reset;
        $user->forceSave();
        $email = $user->email;
        $data  = ['reset' => $reset];

        \Mail::send(
            ['emails.user.verify-html', 'emails.user.verify-text'], $data, function ($message) use ($email) {
                $message->to($email, $email)->subject('Verify your e-mail address.');
            }
        );
    }

} 