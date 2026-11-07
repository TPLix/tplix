<?php
use Tplix\AstLexer;
class TplixParseBlock
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}