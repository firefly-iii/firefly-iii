<?php
/**
 * ImportObject.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Support\Collection;

class ImportObject
{
    /** @var  Collection */
    public $errors;
    /** @var ImportAccount */
    private $asset;
    /** @var  string */
    private $hash;
    /** @var ImportAccount */
    private $opposing;
    /** @var ImportTransaction */
    private $transaction;
    /** @var  User */
    private $user;

    /**
     * ImportEntry constructor.
     */
    public function __construct()
    {
        $this->errors      = new Collection;
        $this->transaction = new ImportTransaction;
        $this->asset       = new ImportAccount;
        $this->opposing    = new ImportAccount;
    }

    public function setHash(string $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $array
     */
    public function setValue(array $array)
    {
        switch ($array['role']) {
            default:
                throw new FireflyException(sprintf('ImportObject cannot handle "%s" with value "%s".', $array['role'], $array['value']));
            case 'account-id':
                $this->asset->setAccountId($array['value']);
                break;
        }
        //var_dump($array);
        //exit;
    }

}