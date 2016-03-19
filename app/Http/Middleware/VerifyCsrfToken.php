<?php
declare(strict_types = 1);
namespace FireflyIII\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

/**
 * Class VerifyCsrfToken
 *
 * @package FireflyIII\Http\Middleware
 */
class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except
        = [
            //
        ];
}
