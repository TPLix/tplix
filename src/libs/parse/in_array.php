<?php

/**
 * Parse the content of an {in_array ...} tag into an associative array.
 *
 * {if in_array('test',$arr)}
 * {if 'test' is in $arr}
 * {if 'test'|in_array}
 * {if 'test' is not in $arr}
 * {if 'test'|in_array:'!'}
 *
 */
class TplixParseIn_array
{
    function parse(string $input): array {
        print_r($input);
        die();
    }
}