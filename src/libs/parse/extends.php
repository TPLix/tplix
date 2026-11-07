<?php
use Tplix\AstLexer;
class TplixParseExtends
{
    function parse(string $input=""): array
    {
        if (AstLexer::$nestingLevel !== 0)
        {
        	throw new Exception('{extends} must be top-level (not nested) and Error at line No '.AstLexer::$currentLine);
        }
        return AstLexer::scanAttributes($input);
    } 
}