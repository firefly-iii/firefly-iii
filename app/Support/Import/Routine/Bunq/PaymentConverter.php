<?php
/**
 * PaymentConverter.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\Bunq;

use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use bunq\Model\Generated\Object\LabelMonetaryAccount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account as LocalAccount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Log;

/**
 * Class PaymentConverter
 */
class PaymentConverter
{
    /** @var AccountFactory */
    private $accountFactory;
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var array */
    private $configuration;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $importJobRepos;

    public function __construct()
    {
        $this->accountRepos   = app(AccountRepositoryInterface::class);
        $this->importJobRepos = app(ImportJobRepositoryInterface::class);
        $this->accountFactory = app(AccountFactory::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Convert a bunq transaction to a usable transaction for Firefly III.
     *
     * @param BunqPayment  $payment
     *
     * @param LocalAccount $source
     *
     * @return array
     * @throws FireflyException
     */
    public function convert(BunqPayment $payment, LocalAccount $source): array
    {
        $paymentId = $payment->getId();
        Log::debug(sprintf('Now in convert() for payment with ID #%d', $paymentId));
        Log::debug(sprintf('Source account is assumed to be "%s" (#%d)', $source->name, $source->id));
        $type         = TransactionType::WITHDRAWAL;
        $counterParty = $payment->getCounterpartyAlias();
        $amount       = $payment->getAmount();

        // some debug info:
        Log::debug('Assume its a witdrawal');
        Log::debug(sprintf('Subtype is %s', $payment->getSubType()));
        Log::debug(sprintf('Type is %s', $payment->getType()));
        Log::debug(sprintf('Amount is %s %s', $amount->getCurrency(), $amount->getValue()));

        $expected = AccountType::EXPENSE;
        if (1 === bccomp($amount->getValue(), '0')) {
            // amount + means that its a deposit.
            $expected = AccountType::REVENUE;
            $type     = TransactionType::DEPOSIT;
            Log::debug(sprintf('Amount is %s  %s, so assume this is a deposit.', $amount->getCurrency(), $amount->getValue()));
        }
        Log::debug(sprintf('Now going to convert counter party to Firefly III account. Expect it to be a "%s" account.', $expected));
        $destination = $this->convertToAccount($counterParty, $expected);

        // switch source and destination if necessary.
        if (1 === bccomp($amount->getValue(), '0')) {
            Log::debug('Because amount is > 0, will now swap source with destination.');
            [$source, $destination] = [$destination, $source];
        }

        if ($source->accountType->type === AccountType::ASSET && $destination->accountType->type === AccountType::ASSET) {
            $type = TransactionType::TRANSFER;
            Log::debug('Because both transctions are asset, will make it a transfer.');
        }
        Log::debug(sprintf('Bunq created = %s', $payment->getCreated()));
        $created = new Carbon($payment->getCreated(), 'UTC');
        // correct timezone to system timezone.
        $created->setTimezone(config('app.timezone'));

        $description = $payment->getDescription();
        if ('' === $payment->getDescription() && 'SAVINGS' === $payment->getType()) {
            $description = 'Auto-save for savings goal.';
        }

        $storeData = [
            'user'               => $this->importJob->user_id,
            'type'               => $type,
            'date'               => $created->format('Y-m-d H:i:s'),
            'timestamp'          => $created->toAtomString(),
            'description'        => $description,
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => null,
            'bill_name'          => null,
            'tags'               => [$payment->getType(), $payment->getSubType()],
            'internal_reference' => $paymentId,
            'external_id'        => $paymentId,
            'notes'              => null,
            'bunq_payment_id'    => $paymentId,
            'original-source'    => sprintf('bunq-v%s', config('firefly.version')),
            'transactions'       => [
                // single transaction:
                [
                    'description'           => null,
                    'amount'                => $amount->getValue(),
                    'currency_id'           => null,
                    'currency_code'         => $amount->getCurrency(),
                    'foreign_amount'        => null,
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'budget_id'             => null,
                    'budget_name'           => null,
                    'category_id'           => null,
                    'category_name'         => null,
                    'source_id'             => $source->id,
                    'source_name'           => null,
                    'destination_id'        => $destination->id,
                    'destination_name'      => null,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];
        Log::info(sprintf('Parsed %s: "%s" (%s).', $created->format('Y-m-d H:i:s'), $storeData['description'], $storeData['transactions'][0]['amount']));

        return $storeData;

    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
        $this->accountRepos->setUser($importJob->user);
        $this->importJobRepos->setUser($importJob->user);
        $this->accountFactory->setUser($importJob->user);
        $this->configuration = $this->importJobRepos->getConfiguration($importJob);
    }

    /**
     * @param LabelMonetaryAccount $party
     * @param string               $expectedType
     *
     * @return LocalAccount
     * @throws FireflyException
     */
    private function convertToAccount(LabelMonetaryAccount $party, string $expectedType): LocalAccount
    {
        Log::debug(sprintf('in convertToAccount() with LabelMonetaryAccount'));
        if (null !== $party->getIban()) {
            Log::debug(sprintf('Opposing party has IBAN "%s"', $party->getIban()));

            // find account in 'bunq-iban' array first.
            $bunqIbans = $this->configuration['bunq-iban'] ?? [];
            Log::debug('Bunq ibans configuration is', $bunqIbans);

            if (isset($bunqIbans[$party->getIban()])) {
                Log::debug('IBAN is known in array.');
                $accountId = (int)$bunqIbans[$party->getIban()];
                $result    = $this->accountRepos->findNull($accountId);
                if (null !== $result) {
                    Log::debug(sprintf('Search for #%s (IBAN "%s"), found "%s" (#%d)', $accountId, $party->getIban(), $result->name, $result->id));

                    return $result;
                }
            }

            // find opposing party by IBAN second.
            $result = $this->accountRepos->findByIbanNull($party->getIban(), [$expectedType]);
            if (null !== $result) {
                Log::debug(sprintf('Search for "%s" resulted in account "%s" (#%d)', $party->getIban(), $result->name, $result->id));

                return $result;
            }

            // try to find asset account just in case:
            if ($expectedType !== AccountType::ASSET) {
                $result = $this->accountRepos->findByIbanNull($party->getIban(), [AccountType::ASSET]);
                if (null !== $result) {
                    Log::debug(sprintf('Search for Asset "%s" resulted in account %s (#%d)', $party->getIban(), $result->name, $result->id));

                    return $result;
                }
            }
        }
        Log::debug('Found no account for opposing party, must create a new one.');

        // create new account:
        $data    = [
            'user_id'         => $this->importJob->user_id,
            'iban'            => $party->getIban(),
            'name'            => $party->getLabelUser()->getDisplayName(),
            'account_type_id' => null,
            'account_type'     => $expectedType,
            'virtual_balance'  => null,
            'active'          => true,
        ];
        $account = $this->accountFactory->create($data);
        Log::debug(
            sprintf(
                'Converted label monetary account "%s" to NEW "%s" account "%s" (#%d)',
                $party->getLabelUser()->getDisplayName(),
                $expectedType,
                $account->name, $account->id
            )
        );

        return $account;
    }


}
