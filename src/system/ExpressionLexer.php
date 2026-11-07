<?php
namespace Tplix;
/**
 * ExpressionLexer: Tokenizes a single expression string.
 */
class ExpressionLexer
{
    static $expression;
    static $pos;
    static $line;
    static $tokens;
    static $currentChar;
    static $exprLen;

    static function tokenize($expr, $line)
    {
        self::$expression = $expr;
        self::$pos = 0;
        self::$line= $line;
        self::$exprLen = strlen(self::$expression);
        self::$tokens = [];
        self::readChar(); // Initialize currentChar

        while (self::$currentChar !== null) {
            $char = self::$currentChar;

            if (ctype_space($char)) {
                self::skipWhitespace();
                continue;
            }

            // Use switch for faster character handling
            switch ($char) {
                case '"':
                case "'":
                    self::$tokens[] = self::lexString();
                    break;
                case '$':
                    self::$tokens[] = self::lexVariable();
                    break;
                case '(':
                    self::$tokens[] = ['type' => 'LPAREN', 'value' => '(', 'line' => self::$line, 'pos' => self::$pos - 1];
                    self::readChar();
                    break;
                case ')':
                    self::$tokens[] = ['type' => 'RPAREN', 'value' => ')', 'line' => self::$line, 'pos' => self::$pos - 1];
                    self::readChar();
                    break;
                case '[':
                    self::$tokens[] = ['type' => 'LBRACKET', 'value' => '[', 'line' => self::$line, 'pos' => self::$pos - 1];
                    self::readChar();
                    break;
                case ']':
                    self::$tokens[] = ['type' => 'RBRACKET', 'value' => ']', 'line' => self::$line, 'pos' => self::$pos - 1];
                    self::readChar();
                    break;
                case ',':
                    self::$tokens[] = ['type' => 'COMMA', 'value' => ',', 'line' => self::$line, 'pos' => self::$pos - 1];
                    self::readChar();
                    break;
                case '|':
                    self::$tokens[] = ['type' => 'PIPE', 'value' => '|', 'line' => self::$line, 'pos' => self::$pos - 1];
                    self::readChar();
                    break;
                default:
                    if (ctype_digit($char)) {
                        self::$tokens[] = self::lexNumber();
                    } elseif (ctype_alpha($char) || $char === '_') {
                        self::$tokens[] = self::lexIdentifierOrKeyword();
                    } else {
                        // Handle operators
                        self::$tokens[] = self::lexOperator();
                    }
            }
        }

        self::$tokens[] = ['type' => 'EOF', 'line' => self::$line, 'pos' => self::$pos];
        return self::$tokens;
    }

    static function readChar()
    {
        if (self::$pos >= self::$exprLen) {
            self::$currentChar = null;
        } else {
            self::$currentChar = self::$expression[self::$pos];
            self::$pos++;
        }
    }

    static function peekChar()
    {
        $peekPos = self::$pos;
        if ($peekPos >= self::$exprLen) {
            return null;
        }
        return self::$expression[$peekPos];
    }

    static function skipWhitespace()
    {
        while (self::$currentChar !== null && ctype_space(self::$currentChar)) {
            if (self::$currentChar === "\n") {
                self::$line++;
            }
            self::readChar();
        }
    }

    static function lexString()
    {
        $startPos = self::$pos - 1;
        $quote = self::$currentChar;
        $value = self::$currentChar;
        self::readChar(); // Skip opening quote

        while (self::$currentChar !== null && self::$currentChar !== $quote) {
            if (self::$currentChar === '\\') {
                $value .= self::$currentChar;
                self::readChar(); // Skip backslash
                if (self::$currentChar !== null) {
                    $value .= self::$currentChar; // Include escaped char
                    self::readChar();
                }
            } else {
                $value .= self::$currentChar;
                self::readChar();
            }
        }

        if (self::$currentChar === $quote) {
            $value .= self::$currentChar;
            self::readChar(); // Skip closing quote
        } else {
            return ['type' => 'ERROR', 'value' => 'Unclosed string: ' . substr(self::$expression, $startPos), 'line' => self::$line, 'pos' => $startPos];
        }

        return ['type' => 'STRING', 'value' => $value, 'line' => self::$line, 'pos' => $startPos];
    }

    static function lexVariable()
    {
        $startPos = self::$pos - 1;
        $name = self::$currentChar; // '$'
        self::readChar();

        while (self::$currentChar !== null && (ctype_alnum(self::$currentChar) || self::$currentChar === '_')) {
            $name .= self::$currentChar;
            self::readChar();
        }

        // Check for array/object access: $var['key'] or $var.key
        while (self::$currentChar !== null) {
            if (self::$currentChar === '[') {
                $name .= self::$currentChar;
                self::readChar();
                $bracketLevel = 1;
                while (self::$currentChar !== null && $bracketLevel > 0) {
                    if (self::$currentChar === '[') $bracketLevel++;
                    if (self::$currentChar === ']') $bracketLevel--;
                    $name .= self::$currentChar;
                    self::readChar();
                }
            } elseif (self::$currentChar === '.') {
                $name .= self::$currentChar;
                self::readChar();
                $part = '';
                while (self::$currentChar !== null && (ctype_alnum(self::$currentChar) || self::$currentChar === '_')) {
                    $part .= self::$currentChar;
                    self::readChar();
                }
                $name .= $part;
            } else {
                break;
            }
        }

        return ['type' => 'VARIABLE', 'name' => $name, 'line' => self::$line, 'pos' => $startPos];
    }

    static function lexNumber()
    {
        $startPos = self::$pos - 1;
        $num = '';

        if (self::$currentChar === '-') {
            $num .= self::$currentChar;
            self::readChar();
        }

        while (self::$currentChar !== null && (ctype_digit(self::$currentChar) || self::$currentChar === '.')) {
            $num .= self::$currentChar;
            self::readChar();
        }

        $type = strpos($num, '.') !== false ? 'FLOAT' : 'INTEGER';

        return ['type' => $type, 'value' => $num, 'line' => self::$line, 'pos' => $startPos];
    }

    static function lexIdentifierOrKeyword()
    {
        $startPos = self::$pos - 1;
        $name = '';

        while (self::$currentChar !== null && (ctype_alnum(self::$currentChar) || self::$currentChar === '_')) {
            $name .= self::$currentChar;
            self::readChar();
        }

        return ['type' => 'IDENTIFIER', 'value' => $name, 'line' => self::$line, 'pos' => $startPos];
    }

    static function lexOperator()
    {
        $startPos = self::$pos - 1;
        $op = self::$currentChar;

        $nextChar = self::peekChar();
        if ($op === '=') {
            if ($nextChar === '=') { $op .= '='; self::readChar(); }
            elseif ($nextChar === '>') { $op .= '>'; self::readChar(); }
        } elseif ($op === '!') {
            if ($nextChar === '=') { $op .= '='; self::readChar(); }
        } elseif ($op === '<') {
            if ($nextChar === '=') { $op .= '='; self::readChar(); }
            elseif ($nextChar === '<') { $op .= '<'; self::readChar(); }
        } elseif ($op === '>') {
            if ($nextChar === '=') { $op .= '='; self::readChar(); }
            elseif ($nextChar === '>') { $op .= '>'; self::readChar(); }
        } elseif ($op === '&') {
            if ($nextChar === '&') { $op .= '&'; self::readChar(); }
        } elseif ($op === '|') {
            if ($nextChar === '|') { $op .= '|'; self::readChar(); }
        } elseif ($op === '*') {
            if ($nextChar === '*') { $op .= '*'; self::readChar(); }
        }

        self::readChar();
        return ['type' => 'OPERATOR', 'value' => $op, 'line' => self::$line, 'pos' => $startPos];
    }
}