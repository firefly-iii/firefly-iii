declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CurrencyCode.
 */
class CurrencyCode implements BinderInterface
{
    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): TransactionCurrency
    {
        // Check if the user is authenticated
        if (!auth()->check()) {
            app('log')->warning('User not authenticated while accessing currency code: ' . $value);
            throw new NotFoundHttpException('User not authenticated.');
        }

        // Trim and validate the input value
        $trimmedValue = trim($value);
        if (empty($trimmedValue)) {
            app('log')->warning('Empty currency code provided.');
            throw new NotFoundHttpException('Currency code cannot be empty.');
        }

        // Find the currency
        $currency = TransactionCurrency::where('code', $trimmedValue)->first();
        if ($currency === null) {
            app('log')->warning('Currency not found for code: ' . $trimmedValue);
            throw new NotFoundHttpException('Currency not found for code: ' . $trimmedValue);
        }

        return $currency;
    }
}
