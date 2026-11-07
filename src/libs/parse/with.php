<?php
use Tplix\AstLexer;
class TplixParseWith
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}