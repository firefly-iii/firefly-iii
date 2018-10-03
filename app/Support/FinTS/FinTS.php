<?php


namespace FireflyIII\Support\FinTS;

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Support\Facades\Crypt;

class FinTS
{
    /** @var \Fhp\FinTs */
    private $finTS;

    /**
     * @param array $config
     * @throws FireflyException
     */
    public function __construct(array $config)
    {
        if (
            !isset($config['fints_url']) or
            !isset($config['fints_port']) or
            !isset($config['fints_bank_code']) or
            !isset($config['fints_username']) or
            !isset($config['fints_password']))
            throw new FireflyException(
                "Constructed FinTS with incomplete config."
            );
        $this->finTS = new \Fhp\FinTs(
            $config['fints_url'],
            $config['fints_port'],
            $config['fints_bank_code'],
            $config['fints_username'],
            Crypt::decrypt($config['fints_password'])
        );
    }

    public function checkConnection()
    {
        try {
            $this->finTS->getSEPAAccounts();
            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @return \Fhp\Model\SEPAAccount[]
     * @throws FireflyException
     */
    public function getAccounts()
    {
        try {
            return $this->finTS->getSEPAAccounts();
        } catch (\Exception $exception) {
            throw new FireflyException($exception->getMessage());
        }
    }

    /**
     * @param string $accountNumber
     * @return \Fhp\Model\SEPAAccount
     * @throws FireflyException
     */
    public function getAccount($accountNumber)
    {
        $accounts         = $this->getAccounts();
        $filteredAccounts = array_filter($accounts, function ($account) use ($accountNumber) {
            return $account->getAccountNumber() == $accountNumber;
        });
        if (count($filteredAccounts) != 1) {
            throw new FireflyException("Cannot find account with number " . $accountNumber);
        }
        return $filteredAccounts[0];
    }

    /**
     * @param \Fhp\Model\SEPAAccount $account
     * @param \DateTime $from
     * @param \DateTIme $to
     * @return \Fhp\Model\StatementOfAccount\StatementOfAccount|null
     * @throws FireflyException
     */
    public function getStatementOfAccount(\Fhp\Model\SEPAAccount $account, \DateTime $from, \DateTIme $to)
    {
        try {
            return $this->finTS->getStatementOfAccount($account, $from, $to);
        } catch (\Exception $exception) {
            throw new FireflyException($exception->getMessage());
        }
    }
}