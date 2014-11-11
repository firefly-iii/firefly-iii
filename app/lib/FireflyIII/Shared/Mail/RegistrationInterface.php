<?php

namespace FireflyIII\Shared\Mail;

/**
 * Interface RegistrationInterface
 *
 * @package FireflyIII\Shared\Mail
 */
interface RegistrationInterface
{

    /**
     * @param \User $user
     *
     * @return mixed
     */
    public function sendVerificationMail(\User $user);

    /**
     * @param \User $user
     *
     * @return mixed
     */
    public function sendPasswordMail(\User $user);

    /**
     * @param \User $user
     *
     * @return mixed
     */
    public function sendResetVerification(\User $user);

} 