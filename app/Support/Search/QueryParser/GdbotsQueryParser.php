<?php

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
     * @return Node[]
     * @throws FireflyException
     */
    public function parse(string $query): array
    {
        try {
            $result = $this->parser->parse($query);
            return array_map(
                fn(GdbotsNode\Node $node) => $this->convertNode($node),
                $result->getNodes()
            );
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
                return new Word($node->getValue());

            case $node instanceof GdbotsNode\Field:
                return new Field(
                    $node->getValue(),
                    (string) $node->getNode()->getValue(),
                    BoolOperator::PROHIBITED === $node->getBoolOperator()
                );

            case $node instanceof GdbotsNode\Subquery:
                return new Subquery(
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
                return new Word((string) $node->getValue());

            default:
                throw new FireflyException(
                    sprintf('Unsupported node type: %s', get_class($node))
                );
        }
    }
}
