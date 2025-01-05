<?php


/*
 * GdbotsQueryParser.php
 * Copyright (c) 2025 https://github.com/Sobuno
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

namespace FireflyIII\Support\Search\QueryParser;

use FireflyIII\Exceptions\FireflyException;
use Gdbots\QueryParser\QueryParser as BaseQueryParser;
use Gdbots\QueryParser\Node as GdbotsNode;
use Gdbots\QueryParser\Enum\BoolOperator;

class GdbotsQueryParser implements QueryParserInterface
{
    private BaseQueryParser $parser;

    public function __construct()
    {
        $this->parser = new BaseQueryParser();
    }

    /**
     * @return NodeGroup
     * @throws FireflyException
     */
    public function parse(string $query): NodeGroup
    {
        try {
            $result = $this->parser->parse($query);
            $nodes = array_map(
                fn(GdbotsNode\Node $node) => $this->convertNode($node),
                $result->getNodes()
            );
            return new NodeGroup($nodes);
        } catch (\LogicException|\TypeError $e) {
            fwrite(STDERR, "Setting up GdbotsQueryParserTest\n");
            dd('Creating GdbotsQueryParser');
            app('log')->error($e->getMessage());
            app('log')->error(sprintf('Could not parse search: "%s".', $query));

            throw new FireflyException(sprintf('Invalid search value "%s". See the logs.', e($query)), 0, $e);
        }
    }

    private function convertNode(GdbotsNode\Node $node): Node
    {
        switch (true) {
            case $node instanceof GdbotsNode\Word:
                return new StringNode($node->getValue());

            case $node instanceof GdbotsNode\Field:
                return new FieldNode(
                    $node->getValue(),
                    (string) $node->getNode()->getValue(),
                    BoolOperator::PROHIBITED === $node->getBoolOperator()
                );

            case $node instanceof GdbotsNode\Subquery:
                return new NodeGroup(
                    array_map(
                        fn(GdbotsNode\Node $subNode) => $this->convertNode($subNode),
                        $node->getNodes()
                    )
                );

            case $node instanceof GdbotsNode\Phrase:
            case $node instanceof GdbotsNode\Numbr:
            case $node instanceof GdbotsNode\Date:
            case $node instanceof GdbotsNode\Url:
            case $node instanceof GdbotsNode\Hashtag:
            case $node instanceof GdbotsNode\Mention:
            case $node instanceof GdbotsNode\Emoticon:
            case $node instanceof GdbotsNode\Emoji:
                return new StringNode((string) $node->getValue());

            default:
                throw new FireflyException(
                    sprintf('Unsupported node type: %s', get_class($node))
                );
        }
    }
}
