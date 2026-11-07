<?php
use Tplix\AstLexer;
use Tplix\Template;
class TplixParseHtml_select_time
{
    function parse(string $input=""): array
    {
    	$allowedKeys = ["prefix","time","display_hours","display_minutes","display_seconds","display_meridian","use_24_hours","minute_interval","second_interval","hour_format","hour_value_format","minute_format","minute_value_format","second_format","second_value_format","field_array","all_extra","hour_extra","minute_extra","second_extra","meridian_extra","field_separator","option_separator","all_id","hour_id","minute_id","second_id","meridian_id","all_empty","hour_empty","minute_empty","second_empty","meridian_empty"];
    	preg_match_all('/(\w+)=(".*?"|\S+)/', $input, $matches, PREG_SET_ORDER);
		$result = [];
		foreach ($matches as $match)
		{
		    $result[$match[1]] = trim($match[2], '"');
		}
		if(isset($result["time"]))
		{
			$result["time"] = Template::compileVariableExpression($result["time"]);
		}
		$invalidKeys = array_diff(array_keys($result), $allowedKeys);
		if(!empty($invalidKeys))
		{
			throw new Exception('{html_select_time} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
		}
		return $result;
    } 
}
