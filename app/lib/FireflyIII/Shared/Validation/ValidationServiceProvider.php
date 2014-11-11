<?php


namespace FireflyIII\Shared\Validation;

use Illuminate\Support\ServiceProvider;

/**
 * Class ValidationServiceProvider
 * @package FireflyIII\Shared\Validation
 */
class ValidationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
    }

    public function register()
    {
    }
} 