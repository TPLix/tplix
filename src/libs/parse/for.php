<?php

/**
 * Parse the content of an {for ...} tag into an associative array.
 *
    {for foo 5}
    {for foo 0 5 2 name="loop1" max="3"}
    {for i 0 5 2}
    {for $foo=1 to 5}
    {for $foo=3 to $to}
    {for $foo=$start to=$to}
 *
 * @param string $tagContent The string inside the for tag (without {for and })
 * @return array Parsed assignment with keys 'var','start','end' and 'step' or additional named parameters
 */
class TplixParseFor
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($tagContent)
    {
        $params = [
            'var'   => null,
            'start' => null,
            'end'   => null,
            'step'  => 1,
        ];
    
        // 1) {for $foo=1 to 5}
        if (preg_match('/^\s*(\$\w+)\s*=\s*([^ ]+)\s+to\s+([^ ]+)/i', $tagContent, $m)) {
            $params['var']   = ltrim($m[1], '$');
            $params['start'] = $m[2];
            $params['end']   = $m[3];
            $tagContent = preg_replace('/^\s*(\$\w+)\s*=\s*([^ ]+)\s+to\s+([^ ]+)/i', '', $tagContent);
        }
        // 2) {for $foo=3 to $to} or {for $foo=$start to=$to}
        elseif (preg_match('/^\s*(\$\w+)\s*=\s*([^ ]+)\s+to=?\s*([^ ]+)/i', $tagContent, $m)) {
            $params['var']   = ltrim($m[1], '$');
            $params['start'] = $m[2];
            $params['end']   = $m[3];
            $tagContent = preg_replace('/^\s*(\$\w+)\s*=\s*([^ ]+)\s+to=?\s*([^ ]+)/i', '', $tagContent);
        }
        // 3) {for foo 0 5}  OR  {for i 0 5 2}
        elseif (preg_match('/^\s*(\w+)\s+(\S+)\s+(\S+)(?:\s+(\S+))?/i', $tagContent, $m)) {
            $params['var']   = $m[1];
            $params['start'] = $m[2];
            $params['end']   = $m[3];
            if (!empty($m[4])) {
                $params['step'] = $m[4];
            }
            $tagContent = preg_replace('/^\s*(\w+)\s+(\S+)\s+(\S+)(?:\s+(\S+))?/i', '', $tagContent);
        }
        // 4) {for foo 5} → means start=0, end=5
        elseif (preg_match('/^\s*(\w+)\s+(\S+)/i', $tagContent, $m)) {
            $params['var']   = $m[1];
            $params['start'] = 0;
            $params['end']   = $m[2];
            $tagContent = preg_replace('/^\s*(\w+)\s+(\S+)/i', '', $tagContent);
        }
        // 5) Parse extra named attributes (name=..., max=..., step=..., etc.)
        if (preg_match_all('/(\w+)\s*=\s*("[^"]*"|\'[^\']*\'|\S+)/', $tagContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name  = $match[1];
                $value = trim($match[2], '\'"');
                $params[$name] = $value;
            }
        }
    
        // Clean up nulls
        $params = array_filter($params, function ($v) { return $v !== null; });
    
        return $params;
    }
}