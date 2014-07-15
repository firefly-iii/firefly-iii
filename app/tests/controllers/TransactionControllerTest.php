<?php

class TransactionControllerTest extends TestCase
{

    public function testCreateWithdrawal()
    {
        View::shouldReceive('share');
        View::shouldReceive('make')->with('transactions.withdrawal')->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()
            ->with('accounts', [])
            ->andReturn(Mockery::self());

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);

        // call
        $this->call('GET', '/transactions/add/withdrawal');

        // test
        $this->assertResponseOk();
    }

} 