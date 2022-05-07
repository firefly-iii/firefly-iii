<?php

/*
 * AttachmentCollection.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Helpers\Collector\Extensions;

use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Log;

/**
 * Trait AttachmentCollection
 */
trait AttachmentCollection
{
    /**
     * @param string $name
     * @return GroupCollectorInterface
     */
    public function attachmentNameContains(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = str_contains(strtolower($attachment['filename']), strtolower($name)) || str_contains(strtolower($attachment['title']), strtolower($name));
                    if (true === $result) {
                        return true;
                    }
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * Has attachments
     *
     * @return GroupCollectorInterface
     */
    public function hasAttachments(): GroupCollectorInterface
    {
        Log::debug('Add filter on attachment ID.');
        $this->joinAttachmentTables();
        $this->query->whereNotNull('attachments.attachable_id');

        return $this;
    }

    /**
     * Join table to get attachment information.
     */
    private function joinAttachmentTables(): void
    {
        if (false === $this->hasJoinedAttTables) {
            // join some extra tables:
            $this->hasJoinedAttTables = true;
            $this->query->leftJoin('attachments', 'attachments.attachable_id', '=', 'transaction_journals.id')
                        ->where(
                            static function (EloquentBuilder $q1) {
                                $q1->where('attachments.attachable_type', TransactionJournal::class);
                                //$q1->where('attachments.uploaded', true);
                                $q1->orWhereNull('attachments.attachable_type');
                            }
                        );
        }
    }

    /**
     * @inheritDoc
     */
    public function withAttachmentInformation(): GroupCollectorInterface
    {
        $this->fields[] = 'attachments.id as attachment_id';
        $this->fields[] = 'attachments.filename as attachment_filename';
        $this->fields[] = 'attachments.title as attachment_title';
        $this->fields[] = 'attachments.uploaded as attachment_uploaded';
        $this->joinAttachmentTables();

        return $this;
    }

    /**
     * @param string $name
     * @return GroupCollectorInterface
     */
    public function attachmentNameEnds(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = str_ends_with(strtolower($attachment['filename']), strtolower($name)) || str_ends_with(strtolower($attachment['title']), strtolower($name));
                    if (true === $result) {
                        return true;
                    }
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * @param string $name
     * @return GroupCollectorInterface
     */
    public function attachmentNameIs(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = $attachment['filename'] === $name || $attachment['title'] === $name;
                    if (true === $result) {
                        return true;
                    }
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * @param string $name
     * @return GroupCollectorInterface
     */
    public function attachmentNameStarts(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = str_starts_with(strtolower($attachment['filename']), strtolower($name)) || str_starts_with(strtolower($attachment['title']), strtolower($name));
                    if (true === $result) {
                        return true;
                    }
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function attachmentNotesAre(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string) $object->notes()?->first()?->text;
                    return $notes !== '' && $notes === $value;
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function attachmentNotesContains(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string) $object->notes()?->first()?->text;
                    return $notes !== '' && str_contains(strtolower($notes), strtolower($value));
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function attachmentNotesEnds(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string) $object->notes()?->first()?->text;
                    return $notes !== '' && str_ends_with(strtolower($notes), strtolower($value));
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function attachmentNotesStarts(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = function (int $index, array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string) $object->notes()?->first()?->text;
                    return $notes !== '' && str_starts_with(strtolower($notes), strtolower($value));
                }
            }
            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * Has attachments
     *
     * @return GroupCollectorInterface
     */
    public function hasNoAttachments(): GroupCollectorInterface
    {
        Log::debug('Add filter on no attachments.');
        $this->joinAttachmentTables();

        $this->query->where(function (Builder $q1) {
            $q1
                ->whereNull('attachments.attachable_id')
                ->orWhere(function (Builder $q2) {
                    $q2
                        ->whereNotNull('attachments.attachable_id')
                        ->whereNotNull('attachments.deleted_at');
                    // id is not null
                    // deleted at is not null.
                });
        });


        return $this;
    }
}
