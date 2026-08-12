<?php




declare(strict_types=1);



namespace FireflyIII\Rules\System;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Safe\Exceptions\UrlException;

use function Safe\parse_url;

class IsValidOriginUrl implements ValidationRule
{
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!auth()->check()) {
            $fail('validation.no_auth_present')->translate();

            return;
        }
        $value = (string) $value;
        if (str_contains($value, '%2F')) {
            $value = urldecode($value);
        }
        if ('' === $value) {
            // string can be empty.
            return;
        }

        try {
            $parts = parse_url($value);
        } catch (UrlException) {
            $fail('validation.bad_url_parts')->translate();

            return;
        }
        if (!array_key_exists('path', $parts) || array_key_exists('scheme', $parts) || array_key_exists('host', $parts)) {
            $fail('validation.bad_url_parts')->translate();

            return;
        }
        if (!str_starts_with($parts['path'], '/')) {
            $fail('validation.bad_url_parts')->translate();

            // return;
        }
    }
}
