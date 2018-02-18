<?php

namespace FireflyIII\Rules;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class BelongsUser
 */
class BelongsUser implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.belongs_user');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     * @throws FireflyException
     */
    public function passes($attribute, $value)
    {
        $attribute = $this->parseAttribute($attribute);
        if (!auth()->check()) {
            return true; // @codeCoverageIgnore
        }
        $attribute = strval($attribute);
        switch ($attribute) {
            case 'piggy_bank_id':
                $count = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                                  ->where('piggy_banks.id', '=', intval($value))
                                  ->where('accounts.user_id', '=', auth()->user()->id)->count();

                return $count === 1;
                break;
            case 'piggy_bank_name':
                $count = $this->countField(PiggyBank::class, 'name', $value);

                return $count === 1;
                break;
            case 'bill_id':
                $count = Bill::where('id', '=', intval($value))->where('user_id', '=', auth()->user()->id)->count();

                return $count === 1;
            case 'bill_name':
                $count = $this->countField(Bill::class, 'name', $value);

                return $count === 1;
                break;
            case 'budget_id':
                $count = Budget::where('id', '=', intval($value))->where('user_id', '=', auth()->user()->id)->count();

                return $count === 1;
                break;
            case 'category_id':
                $count = Category::where('id', '=', intval($value))->where('user_id', '=', auth()->user()->id)->count();

                return $count === 1;
                break;
            case 'budget_name':
                $count = $this->countField(Budget::class, 'name', $value);

                return $count === 1;
                break;
            case 'source_id':
            case 'destination_id':
                $count = Account::where('id', '=', intval($value))->where('user_id', '=', auth()->user()->id)->count();

                return $count === 1;
                break;

            default:
                throw new FireflyException(sprintf('Rule BelongUser cannot handle "%s"', $attribute)); // @codeCoverageIgnore
        }
    }

    /**
     * @param string $class
     * @param string $field
     * @param string $value
     *
     * @return int
     */
    protected function countField(string $class, string $field, string $value): int
    {
        // get all objects belonging to user:
        switch ($class) {
            case PiggyBank::class:
                $objects = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                                    ->where('accounts.user_id', '=', auth()->user()->id)->get(['piggy_banks.*']);
                break;
            default:
                $objects = $class::where('user_id', '=', auth()->user()->id)->get();
                break;
        }
        $count = 0;
        foreach ($objects as $object) {
            if (trim(strval($object->$field)) === trim($value)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string $attribute
     *
     * @return string
     */
    private function parseAttribute(string $attribute): string
    {
        $parts = explode('.', $attribute);
        if (count($parts) === 1) {
            return $attribute;
        }
        if (count($parts) === 3) {
            return $parts[2];
        }

        return $attribute; // @codeCoverageIgnore
    }
}
