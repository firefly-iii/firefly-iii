<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;
use Input;

/**
 * Class BillFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class CurrencyFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    /**
     * @return array
     */
    public function getCurrencyData()
    {
        return [
            'name'   => $this->get('name'),
            'code'   => $this->get('code'),
            'symbol' => $this->get('symbol'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {

        $rules = [
            'code'   => 'required|min:3|max:3|unique:transaction_currencies,code',
            'name'   => 'required|max:48|min:1|unique:transaction_currencies,name',
            'symbol' => 'required|min:1|max:8|unique:transaction_currencies,symbol',
        ];
        if (intval(Input::get('id')) > 0) {
            $rules = [
                'code'   => 'required|min:3|max:3',
                'name'   => 'required|max:48|min:1',
                'symbol' => 'required|min:1|max:8',
            ];
        }

        return $rules;
    }
}
