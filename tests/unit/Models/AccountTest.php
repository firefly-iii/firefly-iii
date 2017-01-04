<?php
/**
 * AccountTest.php
 * Copyright (c) 2016 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

use FireflyIII\Models\Account;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AccountTest extends TestCase
{

    /**
     * @covers \FireflyIII\Models\Account::firstOrCreateEncrypted
     */
    public function testEncrypted()
    {
        $data    = [
            'user_id' => 1,
            'name'    => 'Test account #' . rand(1000, 9999),
        ];
        $account = Account::firstOrCreateEncrypted($data);

        $this->assertEquals($account->user_id, $data['user_id']);
        $this->assertEquals($account->name, $data['name']);
    }

    /**
     * @covers \FireflyIII\Models\Account::firstOrCreateEncrypted
     */
    public function testEncryptedIban()
    {
        $data    = [
            'user_id' => 1,
            'iban'    => 'NL64RABO0133183395',
        ];
        $account = Account::firstOrCreateEncrypted($data);

        $this->assertEquals($account->user_id, $data['user_id']);
        $this->assertEquals($account->name, $data['iban']);
    }

    /**
     * @covers \FireflyIII\Models\Account::firstOrCreateEncrypted
     * @expectedException \FireflyIII\Exceptions\FireflyException
     */
    public function testEncryptedNoId()
    {
        $data    = [
            'name' => 'Test account',
        ];
        $account = Account::firstOrCreateEncrypted($data);
    }

    /**
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testRouteBinder()
    {
        // not logged in?
        $this->be($this->user());
        $this->call('get', route('accounts.show', [1]));

    }

    /**
     * One that belongs to another user.
     *
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testRouteBinderError()
    {
        $account = Account::whereUserId(3)->first();
        $this->be($this->user());
        $this->call('get', route('accounts.show', [$account->id]));
        $this->assertResponseStatus(404);
    }
}