<?php


namespace Tests\Objects;

use Closure;

/**
 * Class Field
 */
class Field
{
    public ?Closure $expectedReturn;
    public string   $expectedReturnType;
    public string   $fieldPosition;
    public string   $fieldTitle;
    public string   $fieldType;
    public ?array   $ignorableFields;
    public string   $title;

}