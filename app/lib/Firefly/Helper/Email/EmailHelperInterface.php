<?php

namespace Firefly\Helper\Email;

interface EmailHelperInterface {

    public function sendVerificationMail(\User $user);
    public function sendPasswordMail(\User $user);
    public function sendResetVerification(\User $user);

} 