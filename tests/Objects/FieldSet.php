<?php


namespace Tests\Objects;

/**
 * Class FieldSet
 */
class FieldSet
{
    public ?array $fields;
    public string $title;

    /**
     * FieldSet constructor.
     */
    public function __construct()
    {
        $this->fields = [];
    }

    /**
     * @param Field       $field
     * @param string|null $key
     */
    public function addField(Field $field, ?string $key = null): void
    {
        if (null === $key) {
            $this->fields[] = $field;

            return;
        }
        $this->fields[$key] = $field;
    }

}