<?php
use Tplix\AstLexer;
class TplixParseTemplate
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}