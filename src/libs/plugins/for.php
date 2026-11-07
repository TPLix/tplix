<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileFor
{
    public function call(array $args = [], int $FOR_ID = 0): string
    {
        $type   = $args['type'] ?? '';
        $hasElse = $args['hasElse'] ?? 0;
        $loopNo = $args['loopNo'] ?? 0;
        $params = $args['params'] ?? [];

        if ($type === 'FOR_START') {
            // Accept: {for foo 5}, {for foo 0 5 2}, {for $foo=1 to 5}, etc.
            $var   = $params['var'] ?? null;
            $start = $params['start'] ?? 0;
            $end   = $params['end']   ?? null;
            $step  = $params['step']  ?? 1;
            $loopName = (isset($params['name']) && is_string($params['name']) && trim($params['name']) !== '') 
                    ? trim($params['name']) 
                    : 'default';

            if (!$var) {
                throw new Exception("For loop requires variable name");
            }
            //if($end[0] != "$") $end = "$".$end;
            $output = '';
            $output .= '$for_' . $loopName . '[' . $FOR_ID . '] = ' . $end . ';' . "\n";
            if($hasElse == 1)
            {
                $output .= Plugins::call('condition',["type"=>"IF_START","condition"=>'isset($for_' . $loopName . '[' . $FOR_ID . '])']);
                $output .="\n";
            }
            $output .='$fl'.$loopNo.'_i = $fl'.$loopNo.'_iteration = $fl'.$loopNo.'_first = $fl'.$loopNo.'_last = $fl'.$loopNo.'_total=0;'."\n";
            $output .='$fl'.$loopNo.'_total=$for_' . $loopName . '[' . $FOR_ID . '];'."\n";
            $output .='$fl'.$loopNo.'_index =-1;'."\n";
            $output .='$fl'.$loopNo.'_show =empty($fl'.$loopNo.'_total) ? false:true;'."\n";            
            $output .= "for(\$$var = $start; \$$var <= \$for_{$loopName}[$FOR_ID]; \$$var += $step):\n";
            $output .='$fl'.$loopNo.'_iteration+=1;'."\n";
            $output .='$fl'.$loopNo.'_i++;'."\n";
            $output .='if($fl'.$loopNo.'_i == 1){$fl'.$loopNo.'_first=1;}else{$fl'.$loopNo.'_first=null;}'."\n";
            $output .='$fl'.$loopNo.'_last = ($fl'.$loopNo.'_iteration === $fl'.$loopNo.'_total);'."\n";
            $output .='$fl'.$loopNo.'_index++;'."\n";            
            return $output;
        }

        if ($type === 'FOR_END') return "endfor;";
        return '';
    }
}
