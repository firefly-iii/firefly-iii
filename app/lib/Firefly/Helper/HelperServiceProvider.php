<?php
namespace Firefly\Helper;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{


    // Triggered automatically by Laravel
    public function register()
    {
        // mail:
        $this->app->bind(
            'Firefly\Helper\Email\EmailHelperInterface',
            'Firefly\Helper\Email\EmailHelper'
        );
    }

}