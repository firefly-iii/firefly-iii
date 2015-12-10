<?php

namespace FireflyIII\Models;

use TestCase;

class AccountTypeTest extends TestCase
{
    public function testAssetAccounts()
    {
        $assetAccounts = AccountType::getAssetAccounts();
        $this->assertCount(2, $assetAccounts);
        $this->assertContains(AccountType::DEFAULT_ACCOUNT, $assetAccounts);
        $this->assertContains(AccountType::ASSET, $assetAccounts);
    }

    public function testExpenseAccounts()
    {
        $expenseAccounts = AccountType::getExpenseAccounts();
        $this->assertCount(2, $expenseAccounts);
        $this->assertContains(AccountType::BENEFICIARY, $expenseAccounts);
        $this->assertContains(AccountType::EXPENSE, $expenseAccounts);
    }

    /**
     * Gets all account types.
     * @return array Returns a list of expense account types.
     */
    public function testAllAccounts()
    {
        $allAccounts = AccountType::getAllAccounts();
        $this->assertCount(3, $allAccounts);
        $this->assertContains(AccountType::DEFAULT_ACCOUNT, $allAccounts);
        $this->assertContains(AccountType::ASSET, $allAccounts);
        $this->assertContains(AccountType::CASH, $allAccounts);
    }

    public function testAllowTransfer()
    {
        $account = $this->getMock('\FireflyIII\Models\Account', array('getAccountType'));
        $account->expects($this->once())->method('getAccountType')->willReturn(AccountType::DEFAULT_ACCOUNT);
        $accountType = new AccountType();
        $this->assertTrue($accountType->allowTransfer($account));
    }

    public function testIsDefault()
    {
        $accountType = AccountType::whereType(AccountType::DEFAULT_ACCOUNT)->first();
        $this->assertTrue($accountType->isDefault());
    }

    public function testIsCash()
    {
        $accountType = AccountType::whereType(AccountType::CASH)->first();
        $this->assertTrue($accountType->isCash());
    }

    public function testIsAsset()
    {
        $accountType = AccountType::whereType(AccountType::ASSET)->first();
        $this->assertTrue($accountType->isAsset());
    }

    public function testIsExpense()
    {
        $accountType = AccountType::whereType(AccountType::EXPENSE)->first();
        $this->assertTrue($accountType->isExpense());
    }

    public function testIsRevenue()
    {
        $accountType = AccountType::whereType(AccountType::REVENUE)->first();
        $this->assertTrue($accountType->isRevenue());
    }

    public function testIsInitialBalance()
    {
        $accountType = AccountType::whereType(AccountType::INITIAL_BALANCE)->first();
        $this->assertTrue($accountType->isInitialBalance());
    }

    public function testIsBeneficiary()
    {
        $accountType = AccountType::whereType(AccountType::BENEFICIARY)->first();
        $this->assertTrue($accountType->isBeneficiary());
    }

    public function testIsImport()
    {
        $accountType = AccountType::whereType(AccountType::IMPORT)->first();
        $this->assertTrue($accountType->isImport());
    }
}
