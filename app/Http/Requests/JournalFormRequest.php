<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
use Exception;
use FireflyIII\Models\TransactionType;
use Input;

/**
 * Class JournalFormRequest
 *
 * @codeCoverageIgnore
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
            'what'                      => $this->get('what'),
            'description'               => $this->get('description'),
            'account_id'                => intval($this->get('account_id')),
            'account_from_id'           => intval($this->get('account_from_id')),
            'account_to_id'             => intval($this->get('account_to_id')),
            'expense_account'           => $this->get('expense_account'),
            'revenue_account'           => $this->get('revenue_account'),
            'amount'                    => round($this->get('amount'), 2),
            'user'                      => Auth::user()->id,
            'amount_currency_id_amount' => intval($this->get('amount_currency_id_amount')),
            'date'                      => new Carbon($this->get('date')),
            'budget_id'                 => intval($this->get('budget_id')),
            'category'                  => $this->get('category'),
            'tags'                      => explode(',', $this->get('tags')),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function rules()
    {
        $what  = Input::get('what');
        $rules = [
            'description'               => 'required|min:1,max:255',
            'what'                      => 'required|in:withdrawal,deposit,transfer',
            'amount'                    => 'numeric|required|min:0.01',
            'date'                      => 'required|date',
            'amount_currency_id_amount' => 'required|exists:transaction_currencies,id',

        ];

        switch ($what) {
            case strtolower(TransactionType::WITHDRAWAL):
                $rules['account_id']      = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['expense_account'] = 'between:1,255';
                $rules['category']        = 'between:1,255';
                if (intval(Input::get('budget_id')) != 0) {
                    $rules['budget_id'] = 'exists:budgets,id|belongsToUser:budgets';
                }
                break;
            case strtolower(TransactionType::DEPOSIT):
                $rules['category']        = 'between:1,255';
                $rules['account_id']      = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['revenue_account'] = 'between:1,255';
                break;
            case strtolower(TransactionType::TRANSFER):
                $rules['account_from_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:account_to_id';
                $rules['account_to_id']   = 'required|exists:accounts,id|belongsToUser:accounts|different:account_from_id';
                $rules['category']        = 'between:1,255';
                break;
            default:
                abort(500, 'Cannot handle ' . $what);
                break;
        }

        return $rules;
    }
}
