<?php

/*
 * IsAllowedGroupAction.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Rules;

use Closure;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Override;

class IsAllowedGroupAction implements ValidationRule
{
    // you need these roles to do anything with any endpoint.
    private array                                 $acceptedRoles = [UserRoleEnum::OWNER, UserRoleEnum::FULL];

    public function __construct(private readonly string $className, private readonly string $methodName) {}

    /**
     * @throws AuthorizationException
     */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ('GET' === $this->methodName) {
            // need at least "read only rights".
            $this->acceptedRoles[] = UserRoleEnum::READ_ONLY;
        }
        if ('GET' !== $this->methodName) {
            // either post, put or delete or something else.. you need more access rights.
            switch ($this->className) {
                default:
                    throw new AuthorizationException(sprintf('Cannot handle class "%s"', $this->className));

                case Account::class:
                    $this->acceptedRoles[] = UserRoleEnum::MANAGE_TRANSACTIONS;

                    break;
            }
        }
        $this->validateUserGroup();
    }

    private function validateUserGroup(): void
    {
        try {
            throw new FireflyException('Here we are');
        } catch (FireflyException $e) {
            Log::error($e->getTraceAsString());
        }

        exit('here we are');
    }
}
