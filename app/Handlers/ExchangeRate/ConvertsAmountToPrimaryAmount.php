<?php




declare(strict_types=1);



namespace FireflyIII\Handlers\ExchangeRate;

use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

class ConvertsAmountToPrimaryAmount
{
    public static function convert(ConversionParameters $params): void
    {
        $amountField                          = $params->amountField;
        $primaryAmountField                   = $params->primaryAmountField;

        if (!Amount::convertToPrimary($params->user)) {
            //            Log::debug(sprintf(
            //                'User does not want to do conversion, no need to convert "%s" and store it in field "%s" for %s #%d.',
            //                $params->amountField,
            //                $params->primaryAmountField,
            //                get_class($params->model),
            //                $params->model->id
            //            ));
            $params->model->{$primaryAmountField} = null;
            $params->model->saveQuietly();

            return;
        }
        if (null === $params->originalCurrency) {
            Log::debug(sprintf(
                'Original currency field is empty, no need to convert %s and store it in field %s for %s #%d.',
                $params->amountField,
                $params->primaryAmountField,
                get_class($params->model),
                $params->model->id
            ));

            return;
        }
        $primaryCurrency                      = Amount::getPrimaryCurrencyByUserGroup($params->user->userGroup);
        Log::debug(sprintf(
            'Will convert amount in field %s from %s to %s and store it in %s',
            $params->originalCurrency->code,
            $primaryCurrency->code,
            $params->amountField,
            $params->primaryAmountField
        ));
        if ($params->originalCurrency->id === $primaryCurrency->id) {
            Log::debug('Both currencies are the same, do nothing.');

            return;
        }

        // field is empty or zero, do nothing.
        $amount                               = (string) $params->model->{$amountField};
        if ('' === $amount || 0 === bccomp($amount, '0')) {
            Log::debug(sprintf('Amount "%s" in field "%s" cannot be used, do nothing.', $amount, $amountField));
            $params->model->{$amountField}        = null;
            $params->model->{$primaryAmountField} = null;
            $params->model->saveQuietly();

            return;
        }
        $converter                            = new ExchangeRateConverter();
        $converter->setUserGroup($params->user->userGroup);
        $converter->setIgnoreSettings(true);
        $newAmount                            = $converter->convert($params->originalCurrency, $primaryCurrency, $params->date, $amount);
        $params->model->{$primaryAmountField} = $newAmount;
        $params->model->saveQuietly();
        Log::debug(sprintf(
            'Converted field "%s" of %s #%d from %s %s to %s %s (in field "%s")',
            $amountField,
            get_class($params->model),
            $params->model->id,
            $params->originalCurrency->code,
            $amount,
            $primaryCurrency->code,
            $newAmount,
            $primaryAmountField
        ));
    }
}
