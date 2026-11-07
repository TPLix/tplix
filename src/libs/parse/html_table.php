<?php
use Tplix\AstLexer;
use Tplix\Template;
class TplixParseHtml_table
{
    function parse(string $input=""): array
    {
    	$allowedKeys = ["loop","cols","flatten_keys","rows","inner","caption","table_attr","th_attr","tr_attr","td_attr","trailpad","hdir","vdirbottom-to-top)","tfoot>","tf_attr","col_attr","cell_format","colspan","rowspan","table_class","thead_attr","tbody_attr","tfoot_attr","thead_class","tbody_class","tfoot_class","th_class","tr_class","td_class","tf_class","tr_class_callback","td_class_callback"];
    	preg_match_all('/(\w+)=(".*?"|\S+)/', $input, $matches, PREG_SET_ORDER);
		$result = [];
		foreach ($matches as $match)
		{
		    $result[$match[1]] = trim($match[2], '"');
		}
		$invalidKeys = array_diff(array_keys($result), $allowedKeys);
		if(!empty($invalidKeys))
		{
			throw new Exception('{html_table} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
		}
		return $result;
    } 
}
