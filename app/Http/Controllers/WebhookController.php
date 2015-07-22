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

    protected $middleware = [];

    /**
     * 
     */
    public function sendgrid()
    {
        var_dump($_POST);
        Log::debug($_POST);

    }

}