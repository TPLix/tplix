<?php

/**
 * Parse the content of an {assign ...} tag into an associative array.
 *
 * Examples supported:
 *  {assign var=running_total value=$running_total+$some_array[$row].some_value}
 *  {assign var=amount value=((10+20)/2)}
 *  {assign ((10+20)/2) amount}
 *  {assign 'test string' myVar}
 *  {assign name="myVar" value="test string"}
 *
 * @param string $input The string inside the assign tag (without {assign and })
 * @return array Parsed assignment with keys 'var' and 'value' or positional keys 0 and 1
 */
class TplixParseAssign
{
    function generate(string $input): array {

        $input = trim($input);
       // $input = rtrim(ltrim($input,"{"),"}");
        //$input = str_replace(["  ","{","}"],[" ","",""],$input);
        $input = str_replace(["  "],[" "],$input);
        $result = [
            'var' => null,
            'value' => null,
        ];
        if($input[0] == "$")
        {
            $count = substr_count($input, '=');
            if($count == 1)
            {
    
                $part = explode("=",$input);
                $result['var'] = $part[0];
                $valueMatch = rtrim(ltrim($part[1],"{"),"}");
                $result['value'] = $valueMatch;
            }else{
                $pos = strrpos($input, ' ');
                $result['var'] = substr($input, $pos + 1);
                $valueMatch = substr($input, $pos);
                $valueMatch = rtrim(ltrim($valueMatch,"{"),"}");
                $result['value'] = $valueMatch;
            }
            return $result;
        }
        if (preg_match('/\bvar\s*=/', $input))
        {
            preg_match('/\bvar\s*=\s*("[^"]*"|\'[^\']*\'|[^\s]+)/', $input, $varMatch);
            preg_match('/\bvalue\s*=\s*(.+)$/s', $input, $valueMatch);
            if (!empty($varMatch[1]))
            {
                $result['var'] = str_replace(["'{",'"{',"}'",'}"',"'$",'"$','"',"'"],"",$varMatch[1]);
            }
            if (isset($valueMatch[1]))
            {
                $valueMatch = rtrim(ltrim($valueMatch[1],"{"),"}");
                $result['value'] = str_replace(["'{",'"{',"}'",'}"'],"",$valueMatch);
            }        
            return $result;
        }
        if (preg_match('/\bname\s*=/', $input))
        {
            preg_match('/\bname\s*=\s*("[^"]*"|\'[^\']*\'|[^\s]+)/', $input, $varMatch);
            preg_match('/\bvalue\s*=\s*(.+)$/s', $input, $valueMatch);
            if (!empty($varMatch[1]))
            {
                $result['var'] = str_replace(["'{",'"{',"}'",'}"',"'$",'"$','"',"'"],"",$varMatch[1]);
            }
            if (isset($valueMatch[1]))
            {
                $valueMatch = rtrim(ltrim($valueMatch[1],"{"),"}");
                $result['value'] = str_replace(["'{",'"{',"}'",'}"'],"",$valueMatch);
            }
            return $result;
        }
        if($input[0] == "'" || $input[0] == '"')
        {
            preg_match_all('/(["\'])(.*?)\1/', $input, $matches);
            $pos = strrpos($input, ' ');
            $result['var'] = substr($input, $pos + 1);
            $valueMatch = rtrim(ltrim($matches[0][0],"{"),"}");
            $result['value'] = $valueMatch;        
            return $result;
        }
        $pos = strrpos($input, ' ');
        $result['var'] = substr($input, $pos + 1);
        $valueMatch = rtrim(ltrim(ubstr($input, 0, $pos),"{"),"}");
        $result['value'] = str_replace(" ","",$valueMatch);
        return $result;
    }
    function parse(string $input): array {
        $output = $this->generate($input);
        $nval = str_replace(['"',"'"],'',$output["value"]);
        if(is_numeric($nval)) $output["value"]=$nval;
        return $output;
    }
}
