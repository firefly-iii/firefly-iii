<?php

namespace Firefly\Helper\Email;

/**
 * Interface EmailHelperInterface
 *
 * @package Firefly\Helper\Email
 */
interface EmailHelperInterface
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