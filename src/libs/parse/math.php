<?php

/**
 * Parse the content of an {math ...} tag into an associative array.
 *
    {math "(a+b)*$c/4" a=3 b=5}
    {math equation="x + y" x=$height y=$width}
    {math equation="height * width / division" height=$row_height width=$row_width division=#col_div#}
    {math equation="(( x + y ) / z )" x=2 y=10 z=2}
    {math equation="x + y" x=4.4444 y=5.0000 format="%.2f"}
 *
 * @param string $tagContent The string inside the math tag (without {math and })
 * @return array Parsed assignment with keys 'equation' or additional named parameters
 */
class TplixParseMath
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($tagContent)
    { 
        $params = [
            'equation' => null, // the main math expression
        ];
    
        // 1) Extract the equation inside quotes (single or double)
        if (preg_match('/^\s*(["\'])(.*?)\1/', $tagContent, $m)) {
            $params['equation'] = $m[2];
            // Remove equation part from tagContent to parse the remaining attributes
            $tagContent = trim(substr($tagContent, strlen($m[0])));
        }
    
        // 2) Parse additional named attributes (variables like a=3, b=$var)
        if (preg_match_all('/(\w+)\s*=\s*(?:(["\'])(.*?)\2|(\$\w+)|(\S+))/i', $tagContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key = $m[1];
                if (!empty($m[3])) {          // quoted string or number
                    $value = $m[3];
                } elseif (!empty($m[4])) {    // variable
                    $value = $m[4];
                } elseif (!empty($m[5])) {    // plain word/number
                    $value = $m[5];
                } else {
                    $value = '';
                }
    
                $params[$key] = $value;
            }
        }
        return $params;
    }
}