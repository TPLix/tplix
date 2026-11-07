<?php
use Tplix\AstLexer;
class TplixParseFetch
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}