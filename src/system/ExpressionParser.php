<?php
namespace Tplix;
/**
 * Optimized ExpressionParser: Parses tokens into an AST.
 * Uses a simple recursive descent parser with operator precedence.
 * Optimized for speed by minimizing function calls and using local variables.
 */
use Tplix\ExpressionLexer;

class ExpressionParser
{
    static $tokens;
    static $pos;
    static $currentToken;
    static $line;

    static function parseExpression($expr, $line)
    {
        self::$tokens = ExpressionLexer::tokenize($expr, $line);
        self::$pos = 0;
        self::$line = $line;
        self::readToken();

        $ast = self::parseExpressionWithPrecedence(0);

        if (self::$currentToken['type'] !== 'EOF') {
            return ['type' => 'ERROR', 'message' => 'Unexpected token: ' . self::$currentToken['type'], 'line' => self::$line];
        }

        return $ast;
    }

    static function parseFunctionArguments($argString, $line)
    {
        $tokens = ExpressionLexer::tokenize($argString, $line);

        $args = [];
        $currentArgTokens = [];
        $parenLevel = 0;
        $bracketLevel = 0;

        $len = count($tokens);
        for ($i = 0; $i < $len; $i++) {
            $token = $tokens[$i];
            if ($token['type'] === 'EOF') break;

            if ($token['type'] === 'LPAREN') $parenLevel++;
            elseif ($token['type'] === 'RPAREN') $parenLevel--;
            elseif ($token['type'] === 'LBRACKET') $bracketLevel++;
            elseif ($token['type'] === 'RBRACKET') $bracketLevel--;

            if ($token['type'] === 'COMMA' && $parenLevel === 0 && $bracketLevel === 0) {
                if (!empty($currentArgTokens)) {
                    $argExpr = self::$tokensToString($currentArgTokens);
                    $args[] = self::parseExpression($argExpr, $line);
                }
                $currentArgTokens = [];
            } else {
                $currentArgTokens[] = $token;
            }
        }
        if (!empty($currentArgTokens)) {
            $argExpr = self::$tokensToString($currentArgTokens);
            $args[] = self::parseExpression($argExpr, $line);
        }
        return $args;
    }
    static function tokensToString($tokens)
    {
        $str = '';
        foreach ($tokens as $token) {
            if (isset($token['value'])) {
                $str .= $token['value'];
            } elseif (isset($token['name'])) {
                $str .= $token['name'];
            }
        }
        return $str;
    }

    static function readToken()
    {
        if (self::$pos < count(self::$tokens)) {
            self::$currentToken = self::$tokens[self::$pos];
            self::$pos++;
        } else {
            self::$currentToken = ['type' => 'EOF'];
        }
    }

    static function parseExpressionWithPrecedence($precedence)
    {
        $left = self::parsePrimary();

        while (self::$currentToken['type'] === 'OPERATOR') {
            $op = self::$currentToken['value'];
            $opPrecedence = self::getPrecedence($op);

            if ($opPrecedence < $precedence) {
                break;
            }

            self::readToken();
            $right = self::parseExpressionWithPrecedence($opPrecedence + 1);

            $left = [
                'type' => 'BINARY_OP',
                'operator' => $op,
                'left' => $left,
                'right' => $right,
                'line' => self::$line
            ];
        }

        return $left;
    }

    static function parsePrimary()
    {
        $token = self::$currentToken;
        $tokenType = $token['type'];
        if ($tokenType === 'INTEGER') {
            self::readToken();
            return ['type' => 'INTEGER', 'value' => (int)$token['value'], 'line' => self::$line];
        } elseif ($tokenType === 'FLOAT') {
            self::readToken();
            return ['type' => 'FLOAT', 'value' => (float)$token['value'], 'line' => self::$line];
        } elseif ($tokenType === 'STRING') {
            self::readToken();
            $cleanValue = substr($token['value'], 1, -1);
            return ['type' => 'STRING', 'value' => $cleanValue, 'line' => self::$line];
        } elseif ($tokenType === 'VARIABLE') {
            self::readToken();
            return ['type' => 'VARIABLE', 'name' => $token['name'], 'line' => self::$line];
        } elseif ($tokenType === 'IDENTIFIER') {
            $name = $token['value'];
            self::readToken();

            if (self::$currentToken['type'] === 'LPAREN') {
                return self::parseFunctionCall($name);
            } else {
                return ['type' => 'IDENTIFIER', 'name' => $name, 'line' => self::$line];
            }
        } elseif ($tokenType === 'LPAREN') {
            self::readToken();
            $expr = self::parseExpressionWithPrecedence(0);
            if (self::$currentToken['type'] !== 'RPAREN') {
                return ['type' => 'ERROR', 'message' => 'Expected )', 'line' => self::$line];
            }
            self::readToken();
            return $expr;
        } elseif ($tokenType === 'OPERATOR' && in_array($token['value'], ['+', '-'])) {
            $op = $token['value'];
            self::readToken();
            $operand = self::parsePrimary();
            return [
                'type' => 'UNARY_OP',
                'operator' => $op,
                'operand' => $operand,
                'line' => self::$line
            ];
        }

        return ['type' => 'ERROR', 'message' => 'Unexpected token: ' . $tokenType, 'line' => self::$line];
    }

    static function parseFunctionCall($name)
    {
        self::readToken(); // Consume '('

        $args = [];
        if (self::$currentToken['type'] !== 'RPAREN') {
            do {
                $args[] = self::parseExpressionWithPrecedence(0);
                if (self::$currentToken['type'] === 'COMMA') {
                    self::readToken();
                } else {
                    break;
                }
            } while (self::$currentToken['type'] !== 'RPAREN');
        }

        if (self::$currentToken['type'] !== 'RPAREN') {
            return ['type' => 'ERROR', 'message' => 'Expected ) in function call', 'line' => self::$line];
        }
        self::readToken(); // Consume ')'

        return [
            'type' => 'FUNCTION_CALL',
            'name' => $name,
            'arguments' => $args,
            'line' => self::$line
        ];
    }

    static function getPrecedence($op)
    {
        // Use a simple array lookup. This could be optimized further with a switch if needed.
        static $precedence = [
            '||' => 1,
            '&&' => 2,
            '==' => 3, '!=' => 3,
            '<' => 4, '>' => 4, '<=' => 4, '>=' => 4,
            '+' => 5, '-' => 5,
            '*' => 6, '/' => 6, '%' => 6,
            '**' => 7,
        ];
        return $precedence[$op] ?? 0;
    }
}