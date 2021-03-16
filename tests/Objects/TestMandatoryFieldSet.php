<?php


namespace Tests\Objects;


class TestMandatoryFieldSet
{
    public string $title;
    public ?array $mandatoryFields;

    /**
     * TestMandatoryFieldSet constructor.
     */
    public function __construct()
    {
        $this->mandatoryFields = [];
    }

    /**
     * @param TestMandatoryField $field
     */
    public function addMandatoryField(TestMandatoryField $field)
    {
        $this->mandatoryFields[] = $field;
    }

}