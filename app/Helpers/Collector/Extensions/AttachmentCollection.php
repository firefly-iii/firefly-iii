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

/**
 * Trait AttachmentCollection
 */
trait AttachmentCollection
{
    public function attachmentNameContains(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();

        /**
         * @param int   $index
         * @param array $object
         *
         * @return bool
         */
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = str_contains(strtolower($attachment['filename']), strtolower($name)) || str_contains(
                        strtolower($attachment['title']),
                        strtolower($name)
                    );
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
     */
    public function hasAttachments(): GroupCollectorInterface
    {
        app('log')->debug('Add filter on attachment ID.');
        $this->joinAttachmentTables();
        $this->query->whereNotNull('attachments.attachable_id');
        $this->query->whereNull('attachments.deleted_at');

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
                    static function (EloquentBuilder $q1): void { // @phpstan-ignore-line
                        $q1->where('attachments.attachable_type', TransactionJournal::class);
                        // $q1->where('attachments.uploaded', true);
                        $q1->whereNull('attachments.deleted_at');
                        $q1->orWhereNull('attachments.attachable_type');
                    }
                )
            ;
        }
    }

    public function withAttachmentInformation(): GroupCollectorInterface
    {
        $this->fields[] = 'attachments.id as attachment_id';
        $this->fields[] = 'attachments.filename as attachment_filename';
        $this->fields[] = 'attachments.title as attachment_title';
        $this->fields[] = 'attachments.uploaded as attachment_uploaded';
        $this->joinAttachmentTables();

        return $this;
    }

    public function attachmentNameDoesNotContain(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();

        /**
         * @param int   $index
         * @param array $object
         *
         * @return bool
         *
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = !str_contains(strtolower($attachment['filename']), strtolower($name)) && !str_contains(
                        strtolower($attachment['title']),
                        strtolower($name)
                    );
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

    public function attachmentNameDoesNotEnd(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();

        /**
         * @param int   $index
         * @param array $object
         *
         * @return bool
         *
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = !str_ends_with(strtolower($attachment['filename']), strtolower($name)) && !str_ends_with(
                        strtolower($attachment['title']),
                        strtolower($name)
                    );
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

    public function attachmentNameDoesNotStart(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();

        /**
         * @param int   $index
         * @param array $object
         *
         * @return bool
         *
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = !str_starts_with(strtolower($attachment['filename']), strtolower($name)) && !str_starts_with(
                        strtolower($attachment['title']),
                        strtolower($name)
                    );
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

    public function attachmentNameEnds(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = str_ends_with(strtolower($attachment['filename']), strtolower($name)) || str_ends_with(
                        strtolower($attachment['title']),
                        strtolower($name)
                    );
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

    public function attachmentNameIs(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($name): bool {
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

    public function attachmentNameIsNot(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = $attachment['filename'] !== $name && $attachment['title'] !== $name;
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

    public function attachmentNameStarts(string $name): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($name): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    $result = str_starts_with(strtolower($attachment['filename']), strtolower($name)) || str_starts_with(
                        strtolower($attachment['title']),
                        strtolower($name)
                    );
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

    public function attachmentNotesAre(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && $notes === $value;
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesAreNot(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && $notes !== $value;
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesContains(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && str_contains(strtolower($notes), strtolower($value));
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesDoNotContain(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && !str_contains(strtolower($notes), strtolower($value));
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesDoNotEnd(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && !str_ends_with(strtolower($notes), strtolower($value));
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesDoNotStart(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && !str_starts_with(strtolower($notes), strtolower($value));
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesEnds(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && str_ends_with(strtolower($notes), strtolower($value));
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function attachmentNotesStarts(string $value): GroupCollectorInterface
    {
        $this->hasAttachments();
        $this->withAttachmentInformation();
        $filter              = static function (array $object) use ($value): bool {
            /** @var array $transaction */
            foreach ($object['transactions'] as $transaction) {
                /** @var array $attachment */
                foreach ($transaction['attachments'] as $attachment) {
                    /** @var null|Attachment $object */
                    $object = auth()->user()->attachments()->find($attachment['id']);
                    $notes  = (string)$object?->notes()->first()?->text;

                    return '' !== $notes && str_starts_with(strtolower($notes), strtolower($value));
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    /**
     * Has attachments
     */
    public function hasNoAttachments(): GroupCollectorInterface
    {
        app('log')->debug('Add filter on no attachments.');
        $this->joinAttachmentTables();

        $this->query->where(static function (Builder $q1): void { // @phpstan-ignore-line
            $q1
                ->whereNull('attachments.attachable_id')
                ->orWhere(static function (Builder $q2): void {
                    $q2
                        ->whereNotNull('attachments.attachable_id')
                        ->whereNotNull('attachments.deleted_at')
                    ;
                    // id is not null
                    // deleted at is not null.
                })
            ;
        });

        return $this;
    }
}
