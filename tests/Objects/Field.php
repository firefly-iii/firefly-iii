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
    public string   $fieldTitle;
    public string   $fieldType;
    public ?array   $ignorableFields;
    public string   $title;

    /**
     * Field constructor.
     */
    public function __construct()
    {
        $this->expectedReturnType = 'equal'; // or 'callback'
        $this->expectedReturn     = null; // or the callback
        $this->ignorableFields    = []; // something like transactions/0/currency_code
        //$optionalField->ignorableFields    = ['some_field', 'transactions/0/another_field', 'rules/2/another_one',]; // something like transactions/0/currency_code
    }

    /**
     * @param string $title
     * @param string $type
     *
     * @return static
     */
    public static function createBasic(string $title, string $type): self
    {
        $field             = new self;
        $field->title      = $title;
        $field->fieldTitle = $title;
        $field->fieldType  = $type;

        return $field;
    }

}