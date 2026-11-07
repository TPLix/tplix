<?php
use Tplix\AstLexer;
use Tplix\Template;
class TplixParseHtml_select_date
{
    function parse(string $input=""): array
    {
    	$allowedKeys = ["prefix","time","start_year","end_year","display_days","display_months","display_years","month_names","month_format","day_format","day_value_format","year_as_text","reverse_years","field_array","day_size","month_size","year_size","all_extra","day_extra","month_extra","year_extra","all_id","day_id","month_id","year_id","field_order","field_separator","month_value_format","all_empty","year_empty","month_empty","day_empty"];
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
			throw new Exception('{html_select_date} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
		}
		return $result;
    } 
}
