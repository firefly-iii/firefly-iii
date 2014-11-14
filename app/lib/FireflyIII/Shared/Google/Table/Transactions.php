<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 13/11/14
 * Time: 21:31
 */

namespace FireflyIII\Shared\Google\Table;


use Firefly\Exception\FireflyException;
use Illuminate\Support\Collection;

class Transactions implements Table
{
    /** @var \Grumpydictator\Gchart\GChart */
    protected $chart;
    /** @var int */
    protected $limit;
    /** @var int */
    protected $offset;
    /** @var bool */
    protected $paging;
    /** @var string */
    protected $reqID;


    public function __construct()
    {
        /** @var \Grumpydictator\Gchart\GChart chart */
        $this->chart = \App::make('gchart');
        $this->chart->addColumn('ID', 'number');
        $this->chart->addColumn('ID_Edit', 'string');
        $this->chart->addColumn('ID_Delete', 'string');
        $this->chart->addColumn('Date', 'date');
        $this->chart->addColumn('Description_URL', 'string');
        $this->chart->addColumn('Description', 'string');
        $this->chart->addColumn('Amount', 'number');
        $this->chart->addColumn('From_URL', 'string');
        $this->chart->addColumn('From', 'string');
        $this->chart->addColumn('To_URL', 'string');
        $this->chart->addColumn('To', 'string');
        $this->chart->addColumn('Budget_URL', 'string');
        $this->chart->addColumn('Budget', 'string');
        $this->chart->addColumn('Category_URL', 'string');
        $this->chart->addColumn('Category', 'string');
    }

    public function addData(Collection $data)
    {
        /** @var \TransactionJournal $entry */
        foreach ($data as $entry) {
            $date           = $entry->date;
            $descriptionURL = route('transactions.show', $entry->id);
            $description    = $entry->description;
            /** @var Transaction $transaction */
            foreach ($entry->transactions as $transaction) {
                if (floatval($transaction->amount) > 0) {
                    $amount = floatval($transaction->amount);
                    $to     = $transaction->account->name;
                    $toURL  = route('accounts.show', $transaction->account->id);
                } else {
                    $from    = $transaction->account->name;
                    $fromURL = route('accounts.show', $transaction->account->id);
                }

            }
            if (isset($entry->budgets[0])) {
                $budgetURL = route('budgets.show', $entry->budgets[0]->id);
                $component = $entry->budgets[0]->name;
            } else {
                $budgetURL = '';
                $component = '';
            }

            if (isset($entry->categories[0])) {
                $categoryURL = route('categories.show', $entry->categories[0]->id);
                $category    = $entry->categories[0]->name;
            } else {
                $categoryURL = '';
                $category    = '';
            }


            $id     = $entry->id;
            $edit   = route('transactions.edit', $entry->id);
            $delete = route('transactions.delete', $entry->id);
            $this->chart->addRow(
                $id, $edit, $delete, $date, $descriptionURL, $description, $amount, $fromURL, $from, $toURL, $to, $budgetURL, $component, $categoryURL,
                $category
            );
        }
    }

    public function generate()
    {
        if ($this->getPaging() && (is_null($this->getLimit()) || is_null($this->getOffset()) || is_null($this->getReqID()))) {
            throw new FireflyException('Cannot page without parameters!');
        }
        $this->chart->generate();
        if($this->getPaging()) {
            $data = [
                'version' => '0.6',
                'reqId' => $this->getReqID(),
                'status' => 'warning',
                'warnings' => [
                    [
                        'reason' => 'data_truncated',
                        'message' => 'Retrieved data was truncated',
                        'detailed_message' => 'Data has been truncated due to userrequest (LIMIT in query)'
                    ]
                ],
                'sig' => '12345',
                'table' => $this->chart->getData()
            ];
            $return = '// Data table response'."\n".'google.visualization.Query.setResponse(' . json_encode($data).');';
            return $return;
        //"version":"0.6","reqId":"0","status":"warning","warnings":[{"reason":"data_truncated","message":"Retrieved data was truncated","detailed_message":"Data has been truncated due to userrequest (LIMIT in query)"}],"sig":"253683512","table
        }



        return \Response::json($this->chart->getData());
    }

    /**
     * @return mixed
     */
    public function getPaging()
    {
        return $this->paging;
    }

    /**
     * @param mixed $paging
     */
    public function setPaging($paging)
    {
        $this->paging = $paging;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getReqID()
    {
        return $this->reqID;
    }

    /**
     * @param string $reqID
     */
    public function setReqID($reqID)
    {
        $this->reqID = $reqID;
    }


}