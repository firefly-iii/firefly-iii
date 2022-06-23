<?php

namespace FireflyIII\Exceptions;

use Exception;

/**
 *
 */
class BadHttpHeaderException extends Exception
{
    public int $statusCode = 406;
}