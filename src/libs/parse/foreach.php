<?php
/**
 * Parse the content of a {foreach ...} tag into an associative array.
 *
 * Supports:
 *   {foreach $arr as $val}
 *   {foreach $arr|split:""|lower as $val}
 *   {foreach $arr as key=>val}
 *   {foreach $arr key=>val}
 *   {foreach from=$arr item=val key=k}
 *   {foreach $arr $key=>$val}
 *   {foreach $arr val}
 *   {foreach $arr}
 */
class TplixParseForeach
{
    protected string $str = '';

    public function parse(string $input): array
    {
        $this->str = trim(str_replace(['$',' as '],['',' '],$input));
        $result = $this->process($this->str);
        if(!isset($result['item'])) $result['item']='fitem';
        if(!isset($result['key']))  $result['key']='fkey';
        return $result;
    }

    protected function process(string $input): array
    {
        $params=[
            'from'=>null,
            'item'=>null,
            'key'=>null,
            'name'=>null,
            'max'=>null,
            'start'=>null,
            'step'=>1,
        ];

        /* ----------------------------
           1. named parameters
        -----------------------------*/

        preg_match_all('/(\w+)\s*=\s*(\[.*\]|array\s*\(.*\)|"[^"]*"|\'[^\']*\'|\S+)/is',
            $input,$matches,PREG_SET_ORDER);
        foreach($matches as $m)
        {
            $k=$m[1];
            $v=trim($m[2]);

            if ($v[0] === '[') {
                // try JSON first
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $v = $decoded;
                } else {
                    // treat as PHP short array
                    $parsed = $this->parsePhpArray($v);
                    if ($parsed !== null) {
                        $v = $parsed;
                    }
                }
            }
            elseif(stripos($v,'array(')===0){
                $v=$this->parsePhpArray($v);
            }
            else{
                $v=trim($v,'"\' ');
            }
            if(array_key_exists($k,$params)){
                $params[$k]=$v;
            }
        }

        if($params['from']!==null)
            return $params;

        /* ----------------------------
           2. key=>item shorthand
        -----------------------------*/

        if(preg_match('/^(\S+)\s+(\w+)\s*=>\s*(\w+)/',$input,$m))
        {
            return [
                'from'=>$m[1],
                'key'=>$m[2],
                'item'=>$m[3]
            ];
        }

        /* ----------------------------
           3. variable forms
        -----------------------------*/

        $parts=preg_split('/\s+/',$input);

        if(count($parts)==3 && strpos($parts[1],'=>')!==false)
        {
            [$key,$item]=explode('=>',$parts[1]);

            return [
                'from'=>$parts[0],
                'key'=>$key,
                'item'=>$item
            ];
        }

        if(count($parts)==2)
        {
            return [
                'from'=>$parts[0],
                'item'=>$parts[1]
            ];
        }

        return [
            'from'=>$parts[0]
        ];
    }

    /* ----------------------------
       JSON array parser
    -----------------------------*/

    protected function parseJsonArray(string $str):array
    {
        $decoded=json_decode($str,true);

        return json_last_error()==JSON_ERROR_NONE
            ? $decoded
            : [];
    }

    /* ----------------------------
       PHP array parser
    -----------------------------*/

    protected function parsePhpArray(string $str): ?array
    {
        try {
            // convert short array to php array syntax
            if ($str[0] === '[') {
                $str = preg_replace('/\[(.*)\]/s', 'array($1)', $str);
            }
            return eval("return ".$str.";");
        }
        catch(\Throwable $e){
            return null;
        }
    }
}