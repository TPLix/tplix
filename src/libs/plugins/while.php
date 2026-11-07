<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileWhile
{
    public function call(array $args = [], int $FOR_ID = 0): string
    {
        $type   = $args['type'] ?? '';
        $loopNo = $args['loopNo'] ?? 0;
        $params = $args['params'] ?? [];

        if ($type === 'WHILE_START')
        {
            $loopName = (isset($params['name']) && is_string($params['name']) && trim($params['name']) !== '') 
                    ? trim($params['name']) 
                    : 'default';
            $raw = $params["raw"];
            $variableParts = explode(" ",$raw);
            $end = $variableParts[0];
            array_shift($variableParts);
            $nextParts = implode(" ",$variableParts);
            if (!$end) {
                throw new Exception("For while requires variable name");
            }
            $output = '';
            $output .= "while({$raw}):\n";
            return $output;
        }

        if ($type === 'WHILE_END') return "endwhile;";
        return '';
    }
}
