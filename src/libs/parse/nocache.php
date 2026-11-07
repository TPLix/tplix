<?php
use Tplix\AstLexer;
class TplixParseNocache
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}