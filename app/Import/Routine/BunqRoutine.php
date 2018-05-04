<?php
/**
 * BunqRoutine.php
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

namespace FireflyIII\Import\Routine;

use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Services\Bunq\Id\DeviceServerId;
use FireflyIII\Services\Bunq\Object\DeviceServer;
use FireflyIII\Services\Bunq\Object\LabelMonetaryAccount;
use FireflyIII\Services\Bunq\Object\MonetaryAccountBank;
use FireflyIII\Services\Bunq\Object\Payment;
use FireflyIII\Services\Bunq\Object\ServerPublicKey;
use FireflyIII\Services\Bunq\Object\UserCompany;
use FireflyIII\Services\Bunq\Object\UserPerson;
use FireflyIII\Services\Bunq\Request\DeviceServerRequest;
use FireflyIII\Services\Bunq\Request\DeviceSessionRequest;
use FireflyIII\Services\Bunq\Request\InstallationTokenRequest;
use FireflyIII\Services\Bunq\Request\ListDeviceServerRequest;
use FireflyIII\Services\Bunq\Request\ListMonetaryAccountRequest;
use FireflyIII\Services\Bunq\Request\ListPaymentRequest;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use FireflyIII\Services\Bunq\Token\SessionToken;
use FireflyIII\Services\IP\IPRetrievalInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;

/**
 * Class BunqRoutine
 *
 * Steps before import:
 * 1) register device complete.
 *
 * Stage: 'initial'.
 *
 * 1) Get an installation token (if not present)
 * 2) Register device (if not found)
 *
 * Stage 'registered'
 *
 * 1) Get a session token. (new session)
 * 2) store user person / user company
 *
 * Stage 'logged-in'
 *
 * Get list of bank accounts
 *
 * Stage 'have-accounts'
 *
 * Map accounts to existing accounts
 *
 * Stage 'do-import'?
 */
class BunqRoutine implements RoutineInterface
{
//    /** @var Collection */
//    public $errors;
//    /** @var Collection */
//    public $journals;
//    /** @var int */
//    public $lines = 0;
//    /** @var AccountFactory */
//    private $accountFactory;
//    /** @var AccountRepositoryInterface */
//    private $accountRepository;
//    /** @var ImportJob */
//    private $job;
//    /** @var TransactionJournalFactory */
//    private $journalFactory;
//    /** @var ImportJobRepositoryInterface */
//    private $repository;
//
//    /**
//     * ImportRoutine constructor.
//     */
//    public function __construct()
//    {
//        $this->journals = new Collection;
//        $this->errors   = new Collection;
//    }
//
//    /**
//     * @return Collection
//     */
//    public function getErrors(): Collection
//    {
//        return $this->errors;
//    }
//
//    /**
//     * @return Collection
//     */
//    public function getJournals(): Collection
//    {
//        return $this->journals;
//    }
//
//    /**
//     * @return int
//     */
//    public function getLines(): int
//    {
//        return $this->lines;
//    }
//
//    /**
//     * @return bool
//     *
//     * @throws FireflyException
//     */
//    public function run(): bool
//    {
//        Log::info(sprintf('Start with import job %s using Bunq.', $this->job->key));
//        set_time_limit(0);
//        // this method continues with the job and is called by whenever a stage is
//        // finished
//        $this->continueJob();
//
//        return true;
//    }
//
//    /**
//     * @param ImportJob $job
//     */
//    public function setJob(ImportJob $job)
//    {
//        $this->job               = $job;
//        $this->repository        = app(ImportJobRepositoryInterface::class);
//        $this->accountRepository = app(AccountRepositoryInterface::class);
//        $this->accountFactory    = app(AccountFactory::class);
//        $this->journalFactory    = app(TransactionJournalFactory::class);
//        $this->repository->setUser($job->user);
//        $this->accountRepository->setUser($job->user);
//        $this->accountFactory->setUser($job->user);
//        $this->journalFactory->setUser($job->user);
//    }
//
//    /**
//     * @throws FireflyException
//     */
//    protected function continueJob()
//    {
//        // if in "configuring"
//        if ('configuring' === $this->getStatus()) {
//            Log::debug('Job is in configuring stage, will do nothing.');
//
//            return;
//        }
//        $stage = $this->getConfig()['stage'] ?? 'unknown';
//        Log::debug(sprintf('Now in continueJob() for stage %s', $stage));
//        switch ($stage) {
//            case 'initial':
//                // register device and get tokens.
//                $this->runStageInitial();
//                $this->continueJob();
//                break;
//            case 'registered':
//                // get all bank accounts of user.
//                $this->runStageRegistered();
//                $this->continueJob();
//                break;
//            case 'logged-in':
//                $this->runStageLoggedIn();
//                break;
//            case 'have-accounts':
//                // do nothing in this stage. Job should revert to config routine.
//                break;
//            case 'have-account-mapping':
//                $this->setStatus('running');
//                $this->runStageHaveAccountMapping();
//
//                break;
//            default:
//                throw new FireflyException(sprintf('No action for stage %s!', $stage));
//                break;
//        }
//    }
//
//    /**
//     * @throws FireflyException
//     */
//    protected function runStageInitial(): void
//    {
//        $this->addStep();
//        Log::debug('In runStageInitial()');
//        $this->setStatus('running');
//
//        // register the device at Bunq:
//        $serverId = $this->registerDevice();
//        Log::debug(sprintf('Found device server with id %d', $serverId->getId()));
//
//        $config          = $this->getConfig();
//        $config['stage'] = 'registered';
//        $this->setConfig($config);
//        $this->addStep();
//    }
//
//    /**
//     * Get a session token + userperson + usercompany. Store it in the job.
//     *
//     * @throws FireflyException
//     */
//    protected function runStageRegistered(): void
//    {
//        $this->addStep();
//        Log::debug('Now in runStageRegistered()');
//        $apiKey            = (string)Preferences::getForUser($this->job->user, 'bunq_api_key')->data;
//        $serverPublicKey   = new ServerPublicKey(Preferences::getForUser($this->job->user, 'bunq_server_public_key', [])->data);
//        $installationToken = $this->getInstallationToken();
//        $request           = new DeviceSessionRequest;
//        $request->setInstallationToken($installationToken);
//        $request->setPrivateKey($this->getPrivateKey());
//        $request->setServerPublicKey($serverPublicKey);
//        $request->setSecret($apiKey);
//        $request->call();
//        $this->addStep();
//
//        Log::debug('Requested new session.');
//
//        $deviceSession = $request->getDeviceSessionId();
//        $userPerson    = $request->getUserPerson();
//        $userCompany   = $request->getUserCompany();
//        $sessionToken  = $request->getSessionToken();
//
//        $config                      = $this->getConfig();
//        $config['device_session_id'] = $deviceSession->toArray();
//        $config['user_person']       = $userPerson->toArray();
//        $config['user_company']      = $userCompany->toArray();
//        $config['session_token']     = $sessionToken->toArray();
//        $config['stage']             = 'logged-in';
//        $this->setConfig($config);
//        $this->addStep();
//
//        Log::debug('Session stored in job.');
//    }
//
//    /**
//     * Shorthand method.
//     */
//    private function addStep(): void
//    {
//        $this->addSteps(1);
//    }
//
//    /**
//     * Shorthand method.
//     *
//     * @param int $count
//     */
//    private function addSteps(int $count): void
//    {
//        $this->repository->addStepsDone($this->job, $count);
//    }
//
//    /**
//     * Shorthand method
//     *
//     * @param int $steps
//     */
//    private function addTotalSteps(int $steps): void
//    {
//        $this->repository->addTotalSteps($this->job, $steps);
//    }
//
//    /**
//     * @param int $paymentId
//     *
//     * @return bool
//     */
//    private function alreadyImported(int $paymentId): bool
//    {
//        $count = TransactionJournalMeta::where('name', 'bunq_payment_id')
//                                       ->where('data', json_encode($paymentId))->count();
//
//        Log::debug(sprintf('Transaction #%d is %d time(s) in the database.', $paymentId, $count));
//
//        return $count > 0;
//    }
//
//    /**
//     * @param LabelMonetaryAccount $party
//     * @param string               $expectedType
//     *
//     * @return Account
//     */
//    private function convertToAccount(LabelMonetaryAccount $party, string $expectedType): Account
//    {
//        Log::debug('in convertToAccount()');
//
//        if ($party->getIban() !== null) {
//            // find opposing party by IBAN first.
//            $result = $this->accountRepository->findByIbanNull($party->getIban(), [$expectedType]);
//            if (null !== $result) {
//                Log::debug(sprintf('Search for %s resulted in account %s (#%d)', $party->getIban(), $result->name, $result->id));
//
//                return $result;
//            }
//
//            // try to find asset account just in case:
//            if ($expectedType !== AccountType::ASSET) {
//                $result = $this->accountRepository->findByIbanNull($party->getIban(), [AccountType::ASSET]);
//                if (null !== $result) {
//                    Log::debug(sprintf('Search for Asset "%s" resulted in account %s (#%d)', $party->getIban(), $result->name, $result->id));
//
//                    return $result;
//                }
//            }
//        }
//
//        // create new account:
//        $data    = [
//            'user_id'         => $this->job->user_id,
//            'iban'            => $party->getIban(),
//            'name'            => $party->getLabelUser()->getDisplayName(),
//            'account_type_id' => null,
//            'accountType'     => $expectedType,
//            'virtualBalance'  => null,
//            'active'          => true,
//
//        ];
//        $account = $this->accountFactory->create($data);
//        Log::debug(
//            sprintf(
//                'Converted label monetary account %s to %s account %s (#%d)',
//                $party->getLabelUser()->getDisplayName(),
//                $expectedType,
//                $account->name, $account->id
//            )
//        );
//
//        return $account;
//    }
//
//    /**
//     * This method creates a new public/private keypair for the user. This isn't really secure, since the key is generated on the fly with
//     * no regards for HSM's, smart cards or other things. It would require some low level programming to get this right. But the private key
//     * is stored encrypted in the database so it's something.
//     */
//    private function createKeyPair(): void
//    {
//        Log::debug('Now in createKeyPair()');
//        $private = Preferences::getForUser($this->job->user, 'bunq_private_key', null);
//        $public  = Preferences::getForUser($this->job->user, 'bunq_public_key', null);
//
//        if (!(null === $private && null === $public)) {
//            Log::info('Already have public and private key, return NULL.');
//
//            return;
//        }
//
//        Log::debug('Generate new key pair for user.');
//        $keyConfig = [
//            'digest_alg'       => 'sha512',
//            'private_key_bits' => 2048,
//            'private_key_type' => OPENSSL_KEYTYPE_RSA,
//        ];
//        // Create the private and public key
//        $res = openssl_pkey_new($keyConfig);
//
//        // Extract the private key from $res to $privKey
//        $privKey = '';
//        openssl_pkey_export($res, $privKey);
//
//        // Extract the public key from $res to $pubKey
//        $pubKey = openssl_pkey_get_details($res);
//
//        Preferences::setForUser($this->job->user, 'bunq_private_key', $privKey);
//        Preferences::setForUser($this->job->user, 'bunq_public_key', $pubKey['key']);
//        Log::debug('Created and stored key pair');
//    }
//
//    /**
//     * Shorthand method.
//     *
//     * @return array
//     */
//    private function getConfig(): array
//    {
//        return $this->repository->getConfiguration($this->job);
//    }
//
//    /**
//     * Try to detect the current device ID (in case this instance has been registered already.
//     *
//     * @return DeviceServerId
//     *
//     * @throws FireflyException
//     */
//    private function getExistingDevice(): ?DeviceServerId
//    {
//        Log::debug('Now in getExistingDevice()');
//        $installationToken = $this->getInstallationToken();
//        $serverPublicKey   = $this->getServerPublicKey();
//        $request           = new ListDeviceServerRequest;
//        $remoteIp          = $this->getRemoteIp();
//        $request->setInstallationToken($installationToken);
//        $request->setServerPublicKey($serverPublicKey);
//        $request->setPrivateKey($this->getPrivateKey());
//        $request->call();
//        $devices = $request->getDevices();
//        /** @var DeviceServer $device */
//        foreach ($devices as $device) {
//            if ($device->getIp() === $remoteIp) {
//                Log::debug(sprintf('This instance is registered as device #%s', $device->getId()->getId()));
//
//                return $device->getId();
//            }
//        }
//        Log::info('This instance is not yet registered.');
//
//        return null;
//    }
//
//    /**
//     * Shorthand method.
//     *
//     * @return array
//     */
//    private function getExtendedStatus(): array
//    {
//        return $this->repository->getExtendedStatus($this->job);
//    }
//
//    /**
//     * Get the installation token, either from the users preferences or from Bunq.
//     *
//     * @return InstallationToken
//     *
//     * @throws FireflyException
//     */
//    private function getInstallationToken(): InstallationToken
//    {
//        Log::debug('Now in getInstallationToken().');
//        $token = Preferences::getForUser($this->job->user, 'bunq_installation_token', null);
//        if (null !== $token) {
//            Log::debug('Have installation token, return it.');
//
//            return new InstallationToken($token->data);
//        }
//        Log::debug('Have no installation token, request one.');
//
//        // verify bunq api code:
//        $publicKey = $this->getPublicKey();
//        $request   = new InstallationTokenRequest;
//        $request->setPublicKey($publicKey);
//        $request->call();
//        Log::debug('Sent request for installation token.');
//
//        $installationToken = $request->getInstallationToken();
//        $installationId    = $request->getInstallationId();
//        $serverPublicKey   = $request->getServerPublicKey();
//
//        Log::debug('Have all values from InstallationTokenRequest');
//
//
//        Preferences::setForUser($this->job->user, 'bunq_installation_token', $installationToken->toArray());
//        Preferences::setForUser($this->job->user, 'bunq_installation_id', $installationId->toArray());
//        Preferences::setForUser($this->job->user, 'bunq_server_public_key', $serverPublicKey->toArray());
//
//        Log::debug('Stored token, ID and pub key.');
//
//        return $installationToken;
//    }
//
//    /**
//     * Get the private key from the users preferences.
//     *
//     * @return string
//     */
//    private function getPrivateKey(): string
//    {
//        Log::debug('In getPrivateKey()');
//        $preference = Preferences::getForUser($this->job->user, 'bunq_private_key', null);
//        if (null === $preference) {
//            Log::debug('private key is null');
//            // create key pair
//            $this->createKeyPair();
//        }
//        $preference = Preferences::getForUser($this->job->user, 'bunq_private_key', null);
//        Log::debug('Return private key for user');
//
//        return (string)$preference->data;
//    }
//
//    /**
//     * Get a public key from the users preferences.
//     *
//     * @return string
//     */
//    private function getPublicKey(): string
//    {
//        Log::debug('Now in getPublicKey()');
//        $preference = Preferences::getForUser($this->job->user, 'bunq_public_key', null);
//        if (null === $preference) {
//            Log::debug('public key is NULL.');
//            // create key pair
//            $this->createKeyPair();
//        }
//        $preference = Preferences::getForUser($this->job->user, 'bunq_public_key', null);
//        Log::debug('Return public key for user');
//
//        return (string)$preference->data;
//    }
//
//    /**
//     * Request users server remote IP. Let's assume this value will not change any time soon.
//     *
//     * @return string
//     *
//     */
//    private function getRemoteIp(): ?string
//    {
//
//        $preference = Preferences::getForUser($this->job->user, 'external_ip', null);
//        if (null === $preference) {
//
//            /** @var IPRetrievalInterface $service */
//            $service  = app(IPRetrievalInterface::class);
//            $serverIp = $service->getIP();
//            if (null !== $serverIp) {
//                Preferences::setForUser($this->job->user, 'external_ip', $serverIp);
//            }
//
//            return $serverIp;
//        }
//
//        return $preference->data;
//    }
//
//    /**
//     * Get the public key of the server, necessary to verify server signature.
//     *
//     * @return ServerPublicKey
//     *
//     * @throws FireflyException
//     */
//    private function getServerPublicKey(): ServerPublicKey
//    {
//        $pref = Preferences::getForUser($this->job->user, 'bunq_server_public_key', null)->data;
//        if (null === $pref) {
//            throw new FireflyException('Cannot determine bunq server public key, but should have it at this point.');
//        }
//
//        return new ServerPublicKey($pref);
//    }
//
//    /**
//     * Shorthand method.
//     *
//     * @return string
//     */
//    private function getStatus(): string
//    {
//        return $this->repository->getStatus($this->job);
//    }
//
//    /**
//     * Import the transactions that were found.
//     *
//     * @param array $payments
//     *
//     * @throws FireflyException
//     */
//    private function importPayments(array $payments): void
//    {
//        Log::debug('Going to run importPayments()');
//        $journals = new Collection;
//        $config   = $this->getConfig();
//        foreach ($payments as $accountId => $data) {
//            Log::debug(sprintf('Now running for bunq account #%d with %d payment(s).', $accountId, \count($data['payments'])));
//            /** @var Payment $payment */
//            foreach ($data['payments'] as $index => $payment) {
//                Log::debug(sprintf('Now at payment #%d with ID #%d', $index, $payment->getId()));
//                // store or find counter party:
//                $counterParty = $payment->getCounterParty();
//                $amount       = $payment->getAmount();
//                $paymentId    = $payment->getId();
//                if ($this->alreadyImported($paymentId)) {
//                    Log::error(sprintf('Already imported bunq payment with id #%d', $paymentId));
//
//                    // add three steps to keep up
//                    $this->addSteps(3);
//                    continue;
//                }
//                Log::debug(sprintf('Amount is %s %s', $amount->getCurrency(), $amount->getValue()));
//                $expected = AccountType::EXPENSE;
//                if (bccomp($amount->getValue(), '0') === 1) {
//                    // amount + means that its a deposit.
//                    $expected = AccountType::REVENUE;
//                    Log::debug('Will make opposing account revenue.');
//                }
//                $opposing = $this->convertToAccount($counterParty, $expected);
//                $account  = $this->accountRepository->findNull($config['accounts-mapped'][$accountId]);
//                $type     = TransactionType::WITHDRAWAL;
//
//                $this->addStep();
//
//                Log::debug(sprintf('Will store withdrawal between "%s" (%d) and "%s" (%d)', $account->name, $account->id, $opposing->name, $opposing->id));
//
//                // start storing stuff:
//                $source      = $account;
//                $destination = $opposing;
//                if (bccomp($amount->getValue(), '0') === 1) {
//                    // its a deposit:
//                    $source      = $opposing;
//                    $destination = $account;
//                    $type        = TransactionType::DEPOSIT;
//                    Log::debug('Will make it a deposit.');
//                }
//                if ($account->accountType->type === AccountType::ASSET && $opposing->accountType->type === AccountType::ASSET) {
//                    $type = TransactionType::TRANSFER;
//                    Log::debug('Both are assets, will make transfer.');
//                }
//
//                $storeData = [
//                    'user'               => $this->job->user_id,
//                    'type'               => $type,
//                    'date'               => $payment->getCreated(),
//                    'description'        => $payment->getDescription(),
//                    'piggy_bank_id'      => null,
//                    'piggy_bank_name'    => null,
//                    'bill_id'            => null,
//                    'bill_name'          => null,
//                    'tags'               => [$payment->getType(), $payment->getSubType()],
//                    'internal_reference' => $payment->getId(),
//                    'notes'              => null,
//                    'bunq_payment_id'    => $payment->getId(),
//                    'transactions'       => [
//                        // single transaction:
//                        [
//                            'description'           => null,
//                            'amount'                => $amount->getValue(),
//                            'currency_id'           => null,
//                            'currency_code'         => $amount->getCurrency(),
//                            'foreign_amount'        => null,
//                            'foreign_currency_id'   => null,
//                            'foreign_currency_code' => null,
//                            'budget_id'             => null,
//                            'budget_name'           => null,
//                            'category_id'           => null,
//                            'category_name'         => null,
//                            'source_id'             => $source->id,
//                            'source_name'           => null,
//                            'destination_id'        => $destination->id,
//                            'destination_name'      => null,
//                            'reconciled'            => false,
//                            'identifier'            => 0,
//                        ],
//                    ],
//                ];
//                $journal   = $this->journalFactory->create($storeData);
//                Log::debug(sprintf('Stored journal with ID #%d', $journal->id));
//                $this->addStep();
//                $journals->push($journal);
//
//            }
//        }
//        if ($journals->count() > 0) {
//            // link to tag
//            /** @var TagRepositoryInterface $repository */
//            $repository = app(TagRepositoryInterface::class);
//            $repository->setUser($this->job->user);
//            $data            = [
//                'tag'         => trans('import.import_with_key', ['key' => $this->job->key]),
//                'date'        => new Carbon,
//                'description' => null,
//                'latitude'    => null,
//                'longitude'   => null,
//                'zoomLevel'   => null,
//                'tagMode'     => 'nothing',
//            ];
//            $tag             = $repository->store($data);
//            $extended        = $this->getExtendedStatus();
//            $extended['tag'] = $tag->id;
//            $this->setExtendedStatus($extended);
//
//            Log::debug(sprintf('Created tag #%d ("%s")', $tag->id, $tag->tag));
//            Log::debug('Looping journals...');
//            $tagId = $tag->id;
//
//            foreach ($journals as $journal) {
//                Log::debug(sprintf('Linking journal #%d to tag #%d...', $journal->id, $tagId));
//                DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journal->id, 'tag_id' => $tagId]);
//                $this->addStep();
//            }
//            Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $journals->count(), $tag->id, $tag->tag));
//        }
//
//        // set status to "finished"?
//        // update job:
//        $this->setStatus('finished');
//    }
//
//    /**
//     * To install Firefly III as a new device:
//     * - Send an installation token request.
//     * - Use this token to send a device server request
//     * - Store the installation token
//     * - Use the installation token each time we need a session.
//     *
//     * @throws FireflyException
//     */
//    private function registerDevice(): DeviceServerId
//    {
//        Log::debug('Now in registerDevice()');
//        $deviceServerId = Preferences::getForUser($this->job->user, 'bunq_device_server_id', null);
//        $serverIp       = $this->getRemoteIp();
//        if (null !== $deviceServerId) {
//            Log::debug('Already have device server ID.');
//
//            return new DeviceServerId($deviceServerId->data);
//        }
//
//        Log::debug('Device server ID is null, we have to find an existing one or register a new one.');
//        $installationToken = $this->getInstallationToken();
//        $serverPublicKey   = $this->getServerPublicKey();
//        $apiKey            = Preferences::getForUser($this->job->user, 'bunq_api_key', '');
//        $this->addStep();
//
//        // try get the current from a list:
//        $deviceServerId = $this->getExistingDevice();
//        $this->addStep();
//        if (null !== $deviceServerId) {
//            Log::debug('Found device server ID in existing devices list.');
//
//            return $deviceServerId;
//        }
//
//        Log::debug('Going to create new DeviceServerRequest() because nothing found in existing list.');
//        $request = new DeviceServerRequest;
//        $request->setPrivateKey($this->getPrivateKey());
//        $request->setDescription('Firefly III v' . config('firefly.version') . ' for ' . $this->job->user->email);
//        $request->setSecret($apiKey->data);
//        $request->setPermittedIps([$serverIp]);
//        $request->setInstallationToken($installationToken);
//        $request->setServerPublicKey($serverPublicKey);
//        $deviceServerId = null;
//        // try to register device:
//        try {
//            $request->call();
//            $deviceServerId = $request->getDeviceServerId();
//        } catch (FireflyException $e) {
//            Log::error($e->getMessage());
//            // we really have to quit at this point :(
//            throw new FireflyException($e->getMessage());
//        }
//        if (null === $deviceServerId) {
//            throw new FireflyException('Was not able to register server with bunq. Please see the log files.');
//        }
//
//        Preferences::setForUser($this->job->user, 'bunq_device_server_id', $deviceServerId->toArray());
//        Log::debug(sprintf('Server ID: %s', json_encode($deviceServerId)));
//
//        return $deviceServerId;
//    }
//
//    /**
//     * Will download the transactions for each account that is selected to be imported from.
//     * Will of course also update the number of steps and what-not.
//     *
//     * @throws FireflyException
//     */
//    private function runStageHaveAccountMapping(): void
//    {
//        $config  = $this->getConfig();
//        $user    = new UserPerson($config['user_person']);
//        $mapping = $config['accounts-mapped'];
//        $token   = new SessionToken($config['session_token']);
//        $count   = 0;
//        $all     = [];
//        if (0 === $user->getId()) {
//            $user = new UserCompany($config['user_company']);
//            Log::debug(sprintf('Will try to get transactions for company #%d', $user->getId()));
//        }
//
//        $this->addTotalSteps(\count($config['accounts']) * 2);
//
//        foreach ($config['accounts'] as $accountData) {
//            $this->addStep();
//            $account  = new MonetaryAccountBank($accountData);
//            $importId = $account->getId();
//            if (isset($mapping[$importId])) {
//                Log::debug(sprintf('Will grab payments for account %s', $account->getDescription()));
//                $request = new ListPaymentRequest();
//                $request->setPrivateKey($this->getPrivateKey());
//                $request->setServerPublicKey($this->getServerPublicKey());
//                $request->setSessionToken($token);
//                $request->setUserId($user->getId());
//                $request->setAccount($account);
//                $request->call();
//                $payments = $request->getPayments();
//
//                // store in array
//                $all[$account->getId()] = [
//                    'account'   => $account,
//                    'import_id' => $importId,
//                    'payments'  => $payments,
//                ];
//                $count                  += \count($payments);
//            }
//            Log::debug(sprintf('Total number of payments: %d', $count));
//            $this->addStep();
//            // add steps for import:
//            $this->addTotalSteps($count * 3);
//            $this->importPayments($all);
//        }
//
//        // update job to be complete, I think?
//    }
//
//    /**
//     * @throws FireflyException
//     */
//    private function runStageLoggedIn(): void
//    {
//        $this->addStep();
//        // grab new session token:
//        $config = $this->getConfig();
//        $token  = new SessionToken($config['session_token']);
//        $user   = new UserPerson($config['user_person']);
//        if (0 === $user->getId()) {
//            $user = new UserCompany($config['user_company']);
//        }
//
//        // list accounts request
//        $request = new ListMonetaryAccountRequest();
//        $request->setServerPublicKey($this->getServerPublicKey());
//        $request->setPrivateKey($this->getPrivateKey());
//        $request->setUserId($user->getId());
//        $request->setSessionToken($token);
//        $request->call();
//        $accounts = $request->getMonetaryAccounts();
//        $arr      = [];
//        Log::debug(sprintf('Get monetary accounts, found %d accounts.', $accounts->count()));
//        $this->addStep();
//
//        /** @var MonetaryAccountBank $account */
//        foreach ($accounts as $account) {
//            $arr[] = $account->toArray();
//        }
//
//        $config             = $this->getConfig();
//        $config['accounts'] = $arr;
//        $config['stage']    = 'have-accounts';
//        $this->setConfig($config);
//
//        // once the accounts are stored, go to configuring stage:
//        // update job, set status to "configuring".
//        $this->setStatus('configuring');
//        $this->addStep();
//    }
//
//    /**
//     * Shorthand.
//     *
//     * @param array $config
//     */
//    private function setConfig(array $config): void
//    {
//        $this->repository->setConfiguration($this->job, $config);
//    }
//
//    /**
//     * Shorthand method.
//     *
//     * @param array $extended
//     */
//    private function setExtendedStatus(array $extended): void
//    {
//        $this->repository->setExtendedStatus($this->job, $extended);
//    }
//
//    /**
//     * Shorthand.
//     *
//     * @param string $status
//     */
//    private function setStatus(string $status): void
//    {
//        $this->repository->setStatus($this->job, $status);
//    }
    /**
     * At the end of each run(), the import routine must set the job to the expected status.
     *
     * The final status of the routine must be "provider_finished".
     *
     * @return bool
     * @throws FireflyException
     */
    public function run(): void
    {
        // TODO: Implement run() method.
        throw new NotImplementedException;
    }

    /**
     * @param ImportJob $job
     *
     * @return mixed
     */
    public function setJob(ImportJob $job)
    {
        // TODO: Implement setJob() method.
        throw new NotImplementedException;
    }
}
