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
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class NoteTransformer
 */
class NoteTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * CurrencyTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Transform the note.
     *
     * @param Note $note
     *
     * @return array
     */
    public function transform(Note $note): array
    {
        $converter = new CommonMarkConverter;
        $data      = [
            'id'         => (int)$note->id,
            'created_at' => $note->created_at->toAtomString(),
            'updated_at' => $note->updated_at->toAtomString(),
            'title'      => $note->title,
            'text'       => $note->text,
            'markdown'   => $converter->convertToHtml($note->text),
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/notes/' . $note->id,
                ],
            ],
        ];

        return $data;
    }
}
