<?php
use Tplix\AstLexer;
class TplixParseAuto_escape
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}