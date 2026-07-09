<?php

namespace SumoCoders\FrameworkCoreBundle\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 *  * "MATCH_AGAINST" "(" {StateFieldPathExpression ","}* InParameter {Literal}? ")"
 *  */
class MatchAgainst extends FunctionNode
{
    /** @var array */
    // @phpstan-ignore missingType.iterableValue
    private $columns = [];

    /** @var InputParameter */
    private $needle;

    /** @var Literal */
    private $mode;

    public function parse(Parser $parser): void
    {
        // @phpstan-ignore classConstant.notFound
        $parser->match(Lexer::T_IDENTIFIER);
        // @phpstan-ignore classConstant.notFound
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        // @phpstan-ignore classConstant.notFound
        while ($parser->getLexer()->isNextToken(Lexer::T_IDENTIFIER)) {
            $this->columns[] = $parser->StateFieldPathExpression();
            // @phpstan-ignore classConstant.notFound
            $parser->match(Lexer::T_COMMA);
        }

        // @phpstan-ignore assign.propertyType
        $this->needle = $parser->InParameter();

        // @phpstan-ignore classConstant.notFound
        while ($parser->getLexer()->isNextToken(Lexer::T_STRING)) {
            $this->mode = $parser->Literal();
        }

        // @phpstan-ignore classConstant.notFound
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $haystack = null;
        $first = true;
        foreach ($this->columns as $column) {
            if (!$first) {
                $haystack .= ', ';
            }

            $first = false;

            $haystack .= $column->dispatch($sqlWalker);
        }

        $query = 'MATCH(' . $haystack . ') AGAINST (' . $this->needle->dispatch($sqlWalker);

        // @mago-expect lint:no-else-clause
        // @phpstan-ignore if.alwaysTrue
        if ($this->mode) {
            $query .= ' ' . $this->mode->value . ' )';
        } else {
            $query .= ' )';
        }

        return $query;
    }
}
