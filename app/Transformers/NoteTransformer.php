<?php
/**
 * NoteTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Transformers;


use FireflyIII\Models\Note;
use League\CommonMark\CommonMarkConverter;
use League\Fractal\TransformerAbstract;

/**
 * Class NoteTransformer
 */
class NoteTransformer extends TransformerAbstract
{
    /**
     * @param Note $note
     *
     * @return array
     */
    public function transform(Note $note): array
    {
        $converter = new CommonMarkConverter;

        return [
            'id'           => (int)$note->id,
            'notable_type' => $note->noteable_type,
            'title'        => $note->title,
            'text'         => $note->text,
            'markdown'     => $converter->convertToHtml($note->text),
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/note/' . $note->id,
                ],
            ],
        ];
    }

}