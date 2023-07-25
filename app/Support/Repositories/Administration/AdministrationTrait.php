<?php


/*
 * AdministrationTrait.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Support\Repositories\Administration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Trait AdministrationTrait
 */
trait AdministrationTrait
{
    protected ?int $administrationId = null;
    protected User $user;
    protected ?UserGroup $userGroup = null;

    /**
     * @return int
     */
    public function getAdministrationId(): int
    {
        return $this->administrationId;
    }

    /**
     * @param int $administrationId
     *
     * @throws FireflyException
     */
    public function setAdministrationId(int $administrationId): void
    {
        $this->administrationId = $administrationId;
        $this->refreshAdministration();
    }

    /**
     * @return void
     * @throws FireflyException
     */
    private function refreshAdministration(): void
    {
        if (null !== $this->administrationId) {
            $memberships = GroupMembership::where('user_id', $this->user->id)
                ->where('user_group_id', $this->administrationId)
                ->count();
            if (0 === $memberships) {
                throw new FireflyException(sprintf('User #%d has no access to administration #%d', $this->user->id, $this->administrationId));
            }
            $this->userGroup = UserGroup::find($this->administrationId);
            if (null === $this->userGroup) {
                throw new FireflyException(sprintf('Unfound administration for user #%d', $this->user->id));
            }
            return;
        }
        throw new FireflyException(sprintf('Cannot validate administration for user #%d', $this->user->id));
    }

    /**
     * @param Authenticatable|User|null $user
     *
     * @return void
     */
    public function setUser(Authenticatable|User|null $user): void
    {
        if (null !== $user) {
            $this->user = $user;
        }
    }
}
