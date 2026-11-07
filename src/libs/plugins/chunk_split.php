<?php
/****************
 * {$articleTitle|chunk_split:10:,}
 * *************/
class TplixCompileChunk_split
{
    public function call(array $args = []): mixed
    {
    	$html = $args[0] ?? "";
    	$size = $args[1] ?? 0;
    	$delim = $args[2] ?? "";
	    if (empty($html)) return " ";
	    return chunk_split($html, $size, $delim);
    }
}