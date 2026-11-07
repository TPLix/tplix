<?php

/**
 * Parse the content of an {mailto ...} tag into an associative array.
 *
    {mailto address="me@example.com"}
    {mailto address="me@example.com" text="send me some mail"}
    {mailto address="me@example.com" encode="javascript"}
    {mailto address="me@example.com" encode="hex"}
    {mailto address="me@example.com" subject="Hello to you!"}
    {mailto address="me@example.com" cc="you@example.com,they@example.com"}
    {mailto address="me@example.com" extra='class="email"'}
    {mailto address="me@example.com" encode="javascript_charcode"}
 *
 * @param string $tagContent The string inside the mailto tag (without {mailto and })
 * @return array Parsed assignment with keys 'address','text','encode' and 'subject' or additional named parameters
 */
class TplixParseMailto
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($input)
    { 
        $allowedKeys = ["address","text","encode","cc","bcc","subject","newsgroups","followupto","extra"];
        preg_match_all('/(\w+)=(".*?"|\S+)/', $input, $matches, PREG_SET_ORDER);
        $result = [
            'address' => null,
        ];
        foreach ($matches as $match)
        {
            $result[$match[1]] = trim($match[2], '"');
        }
        $invalidKeys = array_diff(array_keys($result), $allowedKeys);
        if(!empty($invalidKeys))
        {
            throw new Exception('{mailto} Tag have invalid keys at line no '.AstLexer::$currentLine." and invalid keys here: ".implode(", ",$invalidKeys));
        }
        return $result;
    }
}