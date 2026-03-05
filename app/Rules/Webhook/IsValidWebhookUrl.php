<?php

namespace FireflyIII\Rules\Webhook;

use Closure;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

class IsValidWebhookUrl implements ValidationRule
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string)$value;
        $resolved = gethostbyname(parse_url($value, PHP_URL_HOST));
        Log::debug(sprintf('Now validating URL "%s" with IP "%s".', $value, $resolved));

        // IPv4 is allowed to be in 127 range.
        if(filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && IpUtils::checkIp4($resolved, '127.0.0.0/8')) {
            Log::debug(sprintf('Local IP "%s" is allowed', $resolved));
            return;
        }

        if (false === filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
            Log::error(sprintf('The resolved IP address "%s" is invalid.', $resolved));
            $fail('validation.no_reserved_ip')->translate();
        }
        $validProtocols = FireflyConfig::get('valid_url_protocols', config('firefly.valid_url_protocols'))->data;
        $parts = explode(',', $validProtocols);
        $valid = false;
        foreach($parts as $part) {
            if(str_starts_with($value, $part)) {
                $valid = true;
            }
        }
        if(false === $valid) {
            $fail('validation.bad_url_prefix')->translate();
        }
    }
}

