<?php
class TplixParseConfig_load
{
    function parse(string $input=""): array
    {
        $result = [];
        if (strpos($input, 'file=') !== false)
        {
            if (preg_match_all('/(\w+)\s*=\s*"([^"]+)"/', $input, $matches, PREG_SET_ORDER)) {
                $result = [];
                foreach ($matches as $m) {
                    $result[$m[1]] = $m[2];
                }
            }
        }else{
            // 1️⃣ If string starts with "something" (no key before it), treat it as file=
            if (preg_match('/^"([^"]+)"/', $input, $m)) {
                $result['file'] = $m[1];
                // remove it from the string for next matches
                $input = trim(str_replace($m[0], '', $input));
            }
            // 2️⃣ Match key="value" pairs normally
            if (preg_match_all('/(\w+)\s*=\s*"([^"]+)"/', $input, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $result[$m[1]] = $m[2];
                }
            }
        }
        $file = $result['file'];
        if(!file_exists($file)) throw new Exception("config_load <em>".$file."</em> not exist");
        return $result;
    }
}