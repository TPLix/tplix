<?php
use Tplix\AstLexer;
class TplixParseTextformat
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}