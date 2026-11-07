<?php
class TplixParseWhile
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($input)
    {
        $result = [
            'condition' => null,
            'raw' => $input
        ];
    
        // Trim and check if it starts with {while
        $input = trim($input);
        if (preg_match('/^\{while\s+(.+?)\}$/', $input, $matches)) {
            $condition = trim($matches[1]);
            $result['condition'] = $condition;
        }
        return $result;
    }
}