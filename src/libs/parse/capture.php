<?php
use Tplix\AstLexer;
class TplixParseCapture
{
    function parse(string $input=""): array
    {
        $this->process($input);
        return AstLexer::scanAttributes($input);
    } 
    function process(string $input=""): array
    {
        $input = str_replace(['"',"'"],'',$input);
        if (strpos($input, 'name=') !== false)
        {
            preg_match_all('/(\w+)=([^\s]+)/', $input, $matches);
            return array_combine($matches[1], $matches[2]);
        }
        $name = !empty($input) ? $input: null;
        if($name === null) throw new Exception("Capture <em>name</em> parameter missing");
        return ["name"=>$name];
    } 
}