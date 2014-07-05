<?php
namespace Firefly\Helper\Email;

class EmailHelper implements EmailHelperInterface
{
    public function sendVerificationMail(\User $user)
    {

        $reset = \Str::random(32);
        $user->reset = $reset;
        $user->save();
        $email = $user->email;
        $data = ['reset' => $reset];

        \Mail::send(
            ['emails.user.verify-html', 'emails.user.verify-text'], $data, function ($message) use ($email) {
                $message->to($email, $email)->subject('Verify your e-mail address.');
            }
        );
    }

    public function sendPasswordMail(\User $user)
    {

        $password = \Str::random(12);
        $user->password = \Hash::make($password);
        $user->reset = \Str::random(32); // new one.
        $user->save();
        $email = $user->email;


        $data = ['password' => $password];
        \Mail::send(
            ['emails.user.register-html', 'emails.user.register-text'], $data, function ($message) use ($email) {
                $message->to($email, $email)->subject('Welcome to Firefly!');
            }
        );
    }

    public function sendResetVerification(\User $user)
    {
        $reset = \Str::random(32);
        $user->reset = $reset;
        $user->save();
        $email = $user->email;

        $data = ['reset' => $reset];
        \Mail::send(
            ['emails.user.remindme-html', 'emails.user.remindme-text'], $data, function ($message) use ($email) {
                $message->to($email, $email)->subject('Forgot your password?');
            }
        );


    }

} 