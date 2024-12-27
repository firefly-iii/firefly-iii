<?php

declare(strict_types=1);

namespace FireflyIII\Rules\Admin;

use FireflyIII\Support\Validation\ValidatesAmountsTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class IsValidSlackOrDiscordUrl implements ValidationRule
{
    use ValidatesAmountsTrait;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = (string) $value;
        if ('' === $value) {
            return;
        }

        if (!str_starts_with($value, 'https://hooks.slack.com/services/') && !str_starts_with($value, 'https://discord.com/api/webhooks/')) {
            $fail('validation.active_url')->translate();
            $message = sprintf('IsValidSlackUrl: "%s" is not a discord or slack URL.', substr($value, 0, 255));
            Log::debug($message);
            Log::channel('audit')->info($message);
        }
    }
}
