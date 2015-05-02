<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
use Exception;
use Input;

/**
 * Class JournalFormRequest
 *
 * @package FireflyIII\Http\Requests
 */
class JournalFormRequest extends Request
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
    public function getJournalData()
    {
        return [
            'what'               => $this->get('what'),
            'description'        => $this->get('description'),
            'account_id'         => intval($this->get('account_id')),
            'account_from_id'    => intval($this->get('account_from_id')),
            'account_to_id'      => intval($this->get('account_to_id')),
            'expense_account'    => $this->get('expense_account'),
            'revenue_account'    => $this->get('revenue_account'),
            'amount'             => floatval($this->get('amount')),
            'user'               => Auth::user()->id,
            'amount_currency_id' => intval($this->get('amount_currency_id')),
            'date'               => new Carbon($this->get('date')),
            'budget_id'          => intval($this->get('budget_id')),
            'category'           => $this->get('category'),
            'tags'               => explode(',', $this->get('tags')),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function rules()
    {
        // can we switch on the "what"?
        $what = Input::get('what');

        $rules = [
            'description'        => 'required|min:1,max:255',
            'what'               => 'required|in:withdrawal,deposit,transfer|exists:transaction_types,type',
            'amount'             => 'numeric|required|min:0.01',
            'date'               => 'required|date',
            'reminder_id'        => 'numeric|exists:reminders,id',
            'amount_currency_id' => 'required|exists:transaction_currencies,id',

        ];

        switch ($what) {
            case 'withdrawal':
                $rules['account_id']      = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['expense_account'] = 'between:1,255';
                $rules['category']        = 'between:1,255';
                if (intval(Input::get('budget_id')) != 0) {
                    $rules['budget_id'] = 'exists:budgets,id|belongsToUser:budgets';
                }


                break;
            case 'deposit':
                $rules['category']        = 'between:1,255';
                $rules['account_id']      = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['revenue_account'] = 'between:1,255';
                break;
            case 'transfer':
                $rules['account_from_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:account_to_id';
                $rules['account_to_id']   = 'required|exists:accounts,id|belongsToUser:accounts|different:account_from_id';
                $rules['category']        = 'between:1,255';
                break;
            default:
                throw new Exception('Cannot handle ' . $what);
                break;
        }

        return $rules;


    }
}
