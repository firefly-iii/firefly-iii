<?php

/**
 * AttachmentRepositoryInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Repositories\Attachment;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface AttachmentRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface AttachmentRepositoryInterface
{
    public function destroy(Attachment $attachment): bool;

    public function exists(Attachment $attachment): bool;

    public function get(): Collection;

    public function getContent(Attachment $attachment): string;

    /**
     * Get attachment note text or empty string.
     */
    public function getNoteText(Attachment $attachment): ?string;

    /**
     * @throws FireflyException
     */
    public function store(array $data): Attachment;

    public function update(Attachment $attachment, array $attachmentData): Attachment;
}
