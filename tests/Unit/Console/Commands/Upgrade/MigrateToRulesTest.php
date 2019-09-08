<?php
declare(strict_types=1);
/**
 * MigrateToRulesTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Console\Commands\Upgrade;


use FireflyConfig;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class MigrateToRulesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MigrateToRulesTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Basic test. Assume nothing is wrong.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToRules
     */
    public function testHandle(): void
    {

        // mock repositories:
        $userRepository      = $this->mock(UserRepositoryInterface::class);
        $ruleGroupRepository = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepository      = $this->mock(BillRepositoryInterface::class);
        $ruleRepository      = $this->mock(RuleRepositoryInterface::class);
        $group               = $this->user()->ruleGroups()->inRandomOrder()->first();
        $bill                = $this->user()->bills()->inRandomOrder()->first();
        // mock all calls.
        $userRepository->shouldReceive('all')->atLeast()->once()
                       ->andReturn(new Collection([$this->user()]));
        $ruleRepository->shouldReceive('setUser')->atLeast()->once();

        $ruleGroupRepository->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepository->shouldReceive('findByTitle')
                            ->withArgs(['Rule group for bills'])->atLeast()->once()->andReturnNull();
        // rule group repos should try to store a rule group in response to the result above.
        $ruleGroupRepository->shouldReceive('store')->atLeast()->once()->andReturn($group);

        // bill repos should return one rule.
        $billRepository->shouldReceive('setUser')->atLeast()->once();
        $billRepository->shouldReceive('getBills')->atLeast()->once()
                       ->andReturn(new Collection([$bill]));

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_bills_to_rules', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_bills_to_rules', true]);

        // preferences
        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language);


        // assume all is well.
        $this->artisan('firefly-iii:bills-to-rules')
             ->expectsOutput('All bills are OK.')
             ->assertExitCode(0);
    }

    /**
     * Basic test. Give command an unmigrated bill. This bill has the same amount_min
     * as amount_max
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToRules
     */
    public function testHandleEvenBill(): void
    {
        $billName = 'I am a bill #' . $this->randomInt();
        $bill     = Bill::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_currency_id' => null,
                'name'                    => $billName,
                'match'                   => 'some,kind,of,match',
                'amount_min'              => '30',
                'amount_max'              => '30',
                'date'                    => '2019-01-01',
                'repeat_freq'             => 'monthly',
            ]
        );

        // mock repositories:
        $userRepository      = $this->mock(UserRepositoryInterface::class);
        $ruleGroupRepository = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepository      = $this->mock(BillRepositoryInterface::class);
        $ruleRepository      = $this->mock(RuleRepositoryInterface::class);
        $group               = $this->user()->ruleGroups()->inRandomOrder()->first();
        // mock all calls.
        $userRepository->shouldReceive('all')->atLeast()->once()
                       ->andReturn(new Collection([$this->user()]));
        $ruleRepository->shouldReceive('setUser')->atLeast()->once();

        // this is what rule repos should receive:
        $argumentRule = [
            'rule_group_id'   => $group->id,
            'active'          => true,
            'strict'          => false,
            'stop_processing' => false, // field is no longer used.
            'title'           => sprintf('Auto-generated rule for bill "%s"', $billName),
            'description'     => sprintf('This rule is auto-generated to try to match bill "%s".', $billName),
            'trigger'         => 'store-journal',
            'triggers'        => [
                [
                    'type'  => 'description_contains',
                    'value' => 'some kind of match',
                ],
                [
                    'type'  => 'amount_exactly',
                    'value' => $bill->amount_min,
                ],
            ],
            'actions'         => [
                [
                    'type'  => 'link_to_bill',
                    'value' => $bill->name,
                ],
            ],
        ];

        // this is what the bill repos should receive:
        $argumentBill = [
            'currency_id' => $bill->transaction_currency_id,
            'name'        => $bill->name,
            'match'       => 'MIGRATED_TO_RULES',
            'amount_min'  => $bill->amount_min,
            'amount_max'  => $bill->amount_max,
            'date'        => $bill->date,
            'repeat_freq' => $bill->repeat_freq,
            'skip'        => $bill->skip,
            'active'      => $bill->active,
        ];


        $ruleRepository->shouldReceive('store')->atLeast()->once()->withArgs([$argumentRule]);

        // rule group repos should try to store a rule group in response to the result above.
        $ruleGroupRepository->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepository->shouldReceive('findByTitle')
                            ->withArgs(['Rule group for bills'])->atLeast()->once()->andReturnNull();

        $ruleGroupRepository->shouldReceive('store')->atLeast()->once()->andReturn($group);

        // bill repos should return one rule.
        $billRepository->shouldReceive('setUser')->atLeast()->once();
        $billRepository->shouldReceive('getBills')->atLeast()->once()
                       ->andReturn(new Collection([$bill]));
        $billRepository->shouldReceive('update')->atLeast()->once()
                       ->withArgs([Mockery::any(), $argumentBill]);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_bills_to_rules', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_bills_to_rules', true]);

        // preferences
        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language);


        // assume all is well.
        $this->artisan('firefly-iii:bills-to-rules')
             ->expectsOutput('Verified and fixed 1 bill(s).')
             ->assertExitCode(0);
    }

    /**
     * Basic test. Give command an unmigrated bill. This bill has a different amount_min
     * from the amount_max
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToRules
     */
    public function testHandleUnevenBill(): void
    {
        $billName = 'I am a bill #' . $this->randomInt();
        $bill     = Bill::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_currency_id' => null,
                'name'                    => $billName,
                'match'                   => 'some,kind,of,match',
                'amount_min'              => '30',
                'amount_max'              => '40',
                'date'                    => '2019-01-01',
                'repeat_freq'             => 'monthly',
            ]
        );

        // mock repositories:
        $userRepository      = $this->mock(UserRepositoryInterface::class);
        $ruleGroupRepository = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepository      = $this->mock(BillRepositoryInterface::class);
        $ruleRepository      = $this->mock(RuleRepositoryInterface::class);
        $group               = $this->user()->ruleGroups()->inRandomOrder()->first();
        // mock all calls.
        $userRepository->shouldReceive('all')->atLeast()->once()
                       ->andReturn(new Collection([$this->user()]));
        $ruleRepository->shouldReceive('setUser')->atLeast()->once();

        // this is what rule repos should receive:
        $argumentRule = [
            'rule_group_id'   => $group->id,
            'active'          => true,
            'strict'          => false,
            'stop_processing' => false, // field is no longer used.
            'title'           => sprintf('Auto-generated rule for bill "%s"', $billName),
            'description'     => sprintf('This rule is auto-generated to try to match bill "%s".', $billName),
            'trigger'         => 'store-journal',
            'triggers'        => [
                [
                    'type'  => 'description_contains',
                    'value' => 'some kind of match',
                ],
                [
                    'type'  => 'amount_less',
                    'value' => $bill->amount_max,
                ],
                [
                    'type'  => 'amount_more',
                    'value' => $bill->amount_min,
                ],
            ],
            'actions'         => [
                [
                    'type'  => 'link_to_bill',
                    'value' => $bill->name,
                ],
            ],
        ];

        // this is what the bill repos should receive:
        $argumentBill = [
            'currency_id' => $bill->transaction_currency_id,
            'name'        => $bill->name,
            'match'       => 'MIGRATED_TO_RULES',
            'amount_min'  => $bill->amount_min,
            'amount_max'  => $bill->amount_max,
            'date'        => $bill->date,
            'repeat_freq' => $bill->repeat_freq,
            'skip'        => $bill->skip,
            'active'      => $bill->active,
        ];


        $ruleRepository->shouldReceive('store')->atLeast()->once()->withArgs([$argumentRule]);

        // rule group repos should try to store a rule group in response to the result above.
        $ruleGroupRepository->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepository->shouldReceive('findByTitle')
                            ->withArgs(['Rule group for bills'])->atLeast()->once()->andReturnNull();

        $ruleGroupRepository->shouldReceive('store')->atLeast()->once()->andReturn($group);

        // bill repos should return one rule.
        $billRepository->shouldReceive('setUser')->atLeast()->once();
        $billRepository->shouldReceive('getBills')->atLeast()->once()
                       ->andReturn(new Collection([$bill]));
        $billRepository->shouldReceive('update')->atLeast()->once()
                       ->withArgs([Mockery::any(), $argumentBill]);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_bills_to_rules', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_bills_to_rules', true]);

        // preferences
        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language);


        // assume all is well.
        $this->artisan('firefly-iii:bills-to-rules')
             ->expectsOutput('Verified and fixed 1 bill(s).')
             ->assertExitCode(0);
    }

}
