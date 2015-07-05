<?php

namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class OpposingName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class OpposingName extends BasicConverter implements ConverterInterface
{

    /**
     * This method cannot search yet for the correct account (Expense account or Revenue account) because simply put,
     * Firefly doesn't know yet if this account needs to be an Expense account or a Revenue account. This depends
     * on the amount which is in the current row and that's a big unknown.
     *
     * @return mixed
     */
    public function convert()
    {
        return $this->value;
    }
}