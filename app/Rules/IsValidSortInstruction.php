<?php




declare(strict_types=1);



namespace FireflyIII\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidSortInstruction implements ValidationRule
{
    public function __construct(
        private readonly string $class
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $shortClass      = str_replace('FireflyIII\Models\\', '', $this->class);
        if (!is_string($value)) {
            $fail('validation.invalid_sort_instruction')->translate(['object' => $shortClass]);

            return;
        }
        if ('' === $value) {
            // don't validate.

            return;
        }
        $validParameters = config(sprintf('firefly.allowed_sort_parameters.%s', $shortClass));
        if (!is_array($validParameters)) {
            $fail('validation.no_sort_instructions')->translate(['object' => $shortClass]);

            return;
        }
        $parts           = explode(',', $value);
        foreach ($parts as $i => $part) {
            $part = trim($part);
            if (strlen($part) < 2) {
                $fail('validation.invalid_sort_instruction_index')->translate(['index' => $i, 'object' => $shortClass]);

                return;
            }
            if ('-' === $part[0]) {
                $part = substr($part, 1);
            }
            if (!in_array($part, $validParameters, true)) {
                $fail('validation.invalid_sort_instruction_index')->translate(['index' => $i, 'object' => $shortClass]);

                return;
            }
        }
    }
}
