<?php
use Tplix\AstLexer;
class TplixParseDynamic
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}