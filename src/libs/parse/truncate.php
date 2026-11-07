<?php
/**
 * Parse the content of an {truncate ...} tag into an associative array.
 *
    {$articleTitle}
    {$articleTitle|truncate}
    {$articleTitle|truncate:30}
    {$articleTitle|truncate:30:""}
    {$articleTitle|truncate:30:"---"}
    {$articleTitle|truncate:30:"":true}
    {$articleTitle|truncate:30:"...":true}
    {$articleTitle|truncate:30:'..':true:true}
 *
 * @param string $tagContent The string inside the truncate tag (without {truncate and })
 * @return array Parsed assignment with keys 'var','length','etc' and 'middle' or additional named parameters
 */
class TplixParseTruncate
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($tagContent)
    { 
        $params = [
            'var'      => null,   // Variable name
            'length'   => null,   // Truncate length
            'etc'      => null,   // Ellipsis or string to append
            'break_words' => null,// Boolean
            'middle'   => null    // Boolean
        ];
    
        // Match variable with optional modifiers
        // Examples:
        // {$articleTitle}
        // {$articleTitle|truncate}
        // {$articleTitle|truncate:30:"...":true:true}
        if (preg_match('/^\s*\$(\w+)(?:\|truncate(?::(.*))?)?$/', $tagContent, $m)) {
            $params['var'] = '$' . $m[1];
    
            if (isset($m[2])) {
                // Split arguments by ':' but consider quoted strings
                $args = preg_split('/:(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $m[2]);
    
                // Assign args in order
                if (isset($args[0]) && $args[0] !== '') {
                    $params['length'] = $args[0];
                }
                if (isset($args[1]) && $args[1] !== '') {
                    $params['etc'] = trim($args[1], '\'"');
                }
                if (isset($args[2]) && $args[2] !== '') {
                    $params['break_words'] = $args[2] === 'true' ? true : false;
                }
                if (isset($args[3]) && $args[3] !== '') {
                    $params['middle'] = $args[3] === 'true' ? true : false;
                }
            }
        }
        return array_filter($params, function($v) { return $v !== null; });
    }
}