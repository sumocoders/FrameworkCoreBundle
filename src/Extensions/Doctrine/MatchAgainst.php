<?php

namespace SumoCoders\FrameworkCoreBundle\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "MATCH_AGAINST" "(" {StateFieldPathExpression ","}* InParameter {Literal}? ")"
 */
class MatchAgainst extends FunctionNode
{
    /** @var array */
    private $columns = [];

    /** @var string|InputParameter */
    private $needle;

    /** @var Literal */
    private $mode;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        while ($parser->getLexer()->isNextToken(Lexer::T_IDENTIFIER)) {
            $this->columns[] = $parser->StateFieldPathExpression();
            $parser->match(Lexer::T_COMMA);
        };

        $this->needle = $parser->InParameter();

        while ($parser->getLexer()->isNextToken(Lexer::T_STRING)) {
            $this->mode = $parser->Literal();
        }

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

        $query = "MATCH(" . $haystack . ") AGAINST (" . $this->needle->dispatch($sqlWalker);

        if ($this->mode) {
            $query .= " " . $this->mode->value . " )";
        } else {
            $query .= " )";
        }

        return $query;
    }
}
