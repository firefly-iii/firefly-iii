<?php
declare(strict_types=1);

namespace FireflyIII\Exceptions;

use Exception;

/**
 *
 */
class BadHttpHeaderException extends Exception
{
    public int $statusCode = 406;
}
