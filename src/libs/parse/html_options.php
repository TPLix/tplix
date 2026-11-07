<?php
use Tplix\AstLexer;
use Tplix\Template;
class TplixParseHtml_options
{
    function parse(string $input=""): array
    {
    	$allowedKeys = ["name","selectID","selectClasses","optGroupClasses","optionClasses","values","output","selected","options","multiSelection"];
    	preg_match_all('/(\w+)=(".*?"|\S+)/', $input, $matches, PREG_SET_ORDER);
		$result = [];
		foreach ($matches as $match)
		{
		    $result[$match[1]] = trim($match[2], '"');
		}
		if(isset($result["selected"]))
		{
			$result["selected"] = Template::compileVariableExpression($result["selected"]);
		}
		$invalidKeys = array_diff(array_keys($result), $allowedKeys);
		if(!empty($invalidKeys))
		{
			throw new Exception('{html_options} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
		}
		return $result;
    } 
}
