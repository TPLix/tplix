<?php

/**
 * Parse the content of an {is_array ...} tag into an associative array.
 *
 * {if is_array($arr)}
 * {if $arr is array}
 * {if $arr|is_array:'!'}
 *
 */
class TplixParseIs_array
{
    function parse(string $input): array {
        print_r($input);
        die();
    }
}