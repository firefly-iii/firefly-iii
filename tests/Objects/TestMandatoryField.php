<?php


namespace Tests\Objects;
use Closure;

/**
 * Class TestMandatoryField
 */
class TestMandatoryField
{
    public string $title;
    public string $fieldTitle;
    public string $fieldPosition;
    public string $fieldType;
    public string $expectedReturnType;
    public ?Closure $expectedReturn;
    public ?array $ignorableFields;

}