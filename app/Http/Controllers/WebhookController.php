<?php

namespace FireflyIII\Http\Controllers;

use Log;

/**
 * Class WebhookController
 *
 * @package FireflyIII\Http\Controllers
 */
class WebhookController extends Controller
{

    /**
     *
     */
    public function sendgrid()
    {
        var_dump($_POST);
        Log::debug(print_r($_POST, true));
        Log::debug(print_r($_GET, true));
        Log::debug(print_r($_SERVER, true));

    }

}