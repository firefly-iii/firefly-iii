<?php
/**
 * FinTS.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\FinTS;

use Fhp\Model\SEPAAccount;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Support\Facades\Crypt;

/**
 * @codeCoverageIgnore
 * Class FinTS
 */
class FinTS
{
    /** @var \Fhp\FinTs */
    private $finTS;

    /**
     * @param array $config
     *
     * @throws FireflyException
     */
    public function __construct(array $config)
    {
        if (!isset($config['fints_url'], $config['fints_port'], $config['fints_bank_code'], $config['fints_username'], $config['fints_password'])) {
            throw new FireflyException('Constructed FinTS with incomplete config.');
        }
        $this->finTS = new \Fhp\FinTs(
            $config['fints_url'],
            $config['fints_port'],
            $config['fints_bank_code'],
            $config['fints_username'],
            Crypt::decrypt($config['fints_password']) // verified
        );
    }

    /**
     * @return bool|string
     */
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
     * @param string $accountNumber
     *
     * @return SEPAAccount
     * @throws FireflyException
     */
    public function getAccount(string $accountNumber): SEPAAccount
    {
        $accounts         = $this->getAccounts();
        $filteredAccounts = array_filter(
            $accounts, function (SEPAAccount $account) use ($accountNumber) {
            return $account->getAccountNumber() === $accountNumber;
        }
        );
        if (1 !== count($filteredAccounts)) {
            throw new FireflyException(sprintf('Cannot find account with number "%s"', $accountNumber));
        }

        return reset($filteredAccounts);
    }

    /**
     * @return SEPAAccount[]
     * @throws FireflyException
     */
    public function getAccounts(): ?array
    {
        try {
            return $this->finTS->getSEPAAccounts();
        } catch (\Exception $exception) {
            throw new FireflyException($exception->getMessage());
        }
    }

    /**
     * @param SEPAAccount $account
     * @param \DateTime   $from
     * @param \DateTIme   $to
     *
     * @return \Fhp\Model\StatementOfAccount\StatementOfAccount|null
     * @throws FireflyException
     */
    public function getStatementOfAccount(SEPAAccount $account, \DateTime $from, \DateTIme $to)
    {
        try {
            return $this->finTS->getStatementOfAccount($account, $from, $to);
        } catch (\Exception $exception) {
            throw new FireflyException($exception->getMessage());
        }
    }
}
