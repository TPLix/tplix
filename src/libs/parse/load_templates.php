<?php
use Tplix\AstLexer;
class TplixParseLoad_templates
{
    function parse(string $input=""): array
    {
    	return AstLexer::scanAttributes($input);
    } 
}