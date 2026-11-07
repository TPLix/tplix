<?php
use Tplix\AstLexer;
use Tplix\Template;
class TplixParseHtml_image
{
    function parse(string $input=""): array
    {
    	$allowedKeys = ["file","height","width","basedir","alt","href","href_target","path_prefix"];
    	preg_match_all('/(\w+)=(".*?"|\S+)/', $input, $matches, PREG_SET_ORDER);
		$result = [];
		foreach ($matches as $match)
		{
		    $result[$match[1]] = trim($match[2], '"');
		}
		$invalidKeys = array_diff(array_keys($result), $allowedKeys);
		if(!empty($invalidKeys))
		{
			throw new Exception('{html_image} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
		}
		return $result;
    } 
}
