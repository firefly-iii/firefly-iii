<?php

declare(strict_types=1);

namespace Database\Factories;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->name(), 'active' => true];
    }

    public function withType(AccountTypeEnum $type): static
    {
        return $this->for(AccountType::query()->where('type', $type->value)->first());
    }
}
