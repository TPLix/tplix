<?php
use Tplix\AstLexer;
use Tplix\Template;
class TplixParseEval
{
    function parse(string $input=""): array
    {
    	$allowedKeys = ["var","assign"];
    	preg_match_all('/(\w+)=(".*?"|\S+)/', $input, $matches, PREG_SET_ORDER);
		$result = [];
		foreach ($matches as $match)
		{
		    $result[$match[1]] = trim($match[2], '"');
		}
		$invalidKeys = array_diff(array_keys($result), $allowedKeys);
		if(!empty($invalidKeys))
		{
			throw new Exception('{eval} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
		}
		return $result;
    } 
}
