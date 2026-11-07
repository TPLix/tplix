<?php

use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileHtml_table
{
    public function call(array $params = []): string
    {
        $defaults = [
            // Core attributes
            'loop'          => [],
            'cols'          => 3,
            'rows'          => null,
            'inner'         => 'cols',      
            'caption'       => null,
            'table_attr'    => 'border="1"',
            
            // Styling & attributes
            'table_class'   => '',
            'thead_attr'    => '',
            'tbody_attr'    => '',
            'tfoot_attr'    => '',
            'th_attr'       => [],
            'tr_attr'       => [],
            'td_attr'       => [],
            'trailpad'      => '&nbsp;',
            'hdir'          => 'right',
            'vdir'          => 'down',
            
            // Footer & Col support
            'tfoot'         => null,
            'tf_attr'       => [],
            'col_attr'      => [],
            
            // Advanced features
            'cell_format'   => null,
            'colspan'       => [],
            'rowspan'       => [],
            
            // CSS class generators
            'thead_class'   => '',
            'tbody_class'   => '',
            'tfoot_class'   => '',
            'th_class'      => '',
            'tr_class'      => '',
            'td_class'      => '',
            'tf_class'      => '',
            
            // Conditional styling callbacks
            'tr_class_callback' => null,
            'td_class_callback' => null,
            
            // NEW: Associative array flattening
            'flatten_keys'  => [],        // Array of keys to extract from each row
        ];

        $p = array_merge($defaults, $params);
        $arrayParams = [
            'tr_attr',
            'th_attr',
            'td_attr',
            'tf_attr',
            'col_attr', 
            'colspan',
            'rowspan',
            'flatten_keys'
        ];        
        foreach ($arrayParams as $key) {
            $fKey = $p[$key];
            if (isset($fKey)) {
                if (is_string($fKey) && str_starts_with(trim($fKey), '[') && str_ends_with(trim($fKey), ']')) {
                    $p[$key] = json_decode($fKey, true);
                }
            }
        }        
        if (!isset($p['loop'])) {
            trigger_error("html_table: missing 'loop' parameter", E_USER_WARNING);
            return '';
        }

        $loop = (array)$p['loop'];
        $loop_count = count($loop);

        // NEW: Handle associative array flattening
        if ($p['flatten_keys'] && is_array($p['flatten_keys']) && !empty($p['flatten_keys'])) {
       
            $new_loop = [];
            foreach ($loop as $row) {
                if (is_array($row)) {
                    foreach ($p['flatten_keys'] as $key) {
                        $new_loop[] = $row[$key] ?? $p['trailpad'];
                    }
                } else {
                    // If row is not an array, just add it
                    $new_loop[] = $row;
                }
            }
            $loop = $new_loop;
            $loop_count = count($loop);
        } else {
            // Flatten any non-scalar values to prevent serialization
            $new_loop = [];
            foreach ($loop as $item) {
                if (is_array($item)) {
                    foreach ($item as $sub_item) {
                        $new_loop[] = $sub_item;
                    }
                } else {
                    $new_loop[] = $item;
                }
            }
            $loop = $new_loop;
            $loop_count = count($loop);
        }

        // Determine number of columns and column names
        $cols = $p['cols'];
        $num_cols = null;
        $col_names = [];

        if (is_array($cols) && !empty($cols)) {
            $col_names = $cols;
            $num_cols = count($cols);
        } elseif (is_string($cols) && !empty($cols) && !is_numeric($cols)) {
            $col_names = array_map('trim', explode(',', $cols));
            $num_cols = count($col_names);
        } elseif (!empty($cols)) {
            $num_cols = (int)$cols;
        } else {
            $num_cols = $cols; // default 3
        }

        // Determine number of rows
        $rows = $p['rows'];
        $num_rows = null;
        if (empty($p['rows'])) {
            // no rows specified
            $num_rows = (int)ceil($loop_count / $num_cols);
        } elseif (empty($p['cols'])) {
            if (!empty($p['rows'])) {
                // no cols specified, but rows
                $num_cols = (int)ceil($loop_count / $rows);
            }
        } else {
            $num_rows = (int)$rows;
        }

        $output = '<table ' . $p['table_attr'];
        if ($p['table_class']) {
            $output .= ' class="' . htmlspecialchars($p['table_class']) . '"';
        }
        $output .= '>' . "\n";

        // Caption
        if ($p['caption'] !== null) {
            $output .= '<caption>' . htmlspecialchars($p['caption']) . "</caption>\n";
        }

        // Generate <col> elements if col_attr is provided
        if (!empty($p['col_attr']) && is_array($p['col_attr'])) {
            $output .= '<colgroup>' . "\n";
            foreach ($p['col_attr'] as $col_attrs) {
                $output .= '<col ' . $col_attrs . ' />' . "\n";
            }
            $output .= "</colgroup>\n";
        }

        // Generate header row if column names are provided
        if (!empty($col_names)) {
            // Apply hdir to column names
            if ($p['hdir'] === 'left') {
                $col_names = array_reverse($col_names);
            }
            
            $output .= '<thead';
            if ($p['thead_class']) {
                $output .= ' class="' . htmlspecialchars($p['thead_class']) . '"';
            }
            if ($p['thead_attr']) {
                $output .= ' ' . $p['thead_attr'];
            }
            $output .= '>' . "\n";
            
            $output .= '<tr>' . "\n";
            
            for ($c_idx = 0; $c_idx < $num_cols; $c_idx++) {
                $th_attr = $this->cycle('th', $p['th_attr'], $c_idx);
                if ($p['th_class']) {
                    $th_attr .= ' class="' . htmlspecialchars($p['th_class']) . '"';
                }
                
                $output .= '<th' . ($th_attr ? ' ' . trim($th_attr) : '') . '>' . htmlspecialchars($col_names[$c_idx]) . "</th>\n";
            }
            $output .= "</tr>\n";
            $output .= "</thead>\n";
        }

        // Output body rows
        $output .= '<tbody';
        if ($p['tbody_class']) {
            $output .= ' class="' . htmlspecialchars($p['tbody_class']) . '"';
        }
        if ($p['tbody_attr']) {
            $output .= ' ' . $p['tbody_attr'];
        }
        $output .= '>' . "\n";
        
        for ($r_idx = 0; $r_idx < $num_rows; $r_idx++) {
            $tr_attr = $this->cycle('tr', $p['tr_attr'], $r_idx);
            if ($p['tr_class']) {
                $tr_class = $p['tr_class'];
                // If it's cycled (e.g. "odd even"), cycle it
                if (strpos($tr_class, ' ') !== false) {
                    $classes = explode(' ', $tr_class);
                    $tr_class = $classes[$r_idx % count($classes)];
                }
                $tr_attr .= ' class="' . htmlspecialchars($tr_class) . '"';
            }
            
            // Apply conditional row class callback
            if ($p['tr_class_callback'] && is_callable($p['tr_class_callback'])) {
                $callback_class = call_user_func($p['tr_class_callback'], $r_idx, array_slice($loop, $r_idx * $num_cols, $num_cols));
                if ($callback_class) {
                    $tr_attr .= ' class="' . htmlspecialchars(($tr_class ? $tr_class . ' ' : '') . $callback_class) . '"';
                }
            }
            
            $output .= '<tr' . ($tr_attr ? ' ' . trim($tr_attr) : '') . '>' . "\n";

            // Calculate row index based on vdir
            $rx = ($p['vdir'] === 'down') ? $r_idx * $num_cols : ($num_rows - 1 - $r_idx) * $num_cols;
            
            for ($c_idx = 0; $c_idx < $num_cols; $c_idx++) {
                // Calculate cell index based on hdir
                $x = ($p['hdir'] === 'right') ? $rx + $c_idx : $rx + $num_cols - 1 - $c_idx;
                
                // Apply inner logic: shuffle x to loop over rows
                if ($p['inner'] !== 'cols') {
                    $x = floor($x / $num_cols) + ($x % $num_cols) * $num_rows;
                }

                $td_attr = $this->cycle('td', $p['td_attr'], $c_idx);
                if ($p['td_class']) {
                    $td_class = $p['td_class'];
                    // If it's cycled, cycle it
                    if (strpos($td_class, ' ') !== false) {
                        $classes = explode(' ', $td_class);
                        $td_class = $classes[$c_idx % count($classes)];
                    }
                    $td_attr .= ' class="' . htmlspecialchars($td_class) . '"';
                }
                
                // Apply conditional cell class callback
                $cell_value = ($x < $loop_count) ? $loop[$x] : $p['trailpad'];
                if ($p['td_class_callback'] && is_callable($p['td_class_callback'])) {
                    $callback_class = call_user_func($p['td_class_callback'], $cell_value, $r_idx, $c_idx);
                    if ($callback_class) {
                        $td_attr .= ' class="' . htmlspecialchars(($p['td_class'] ? $p['td_class'] . ' ' : '') . $callback_class) . '"';
                    }
                }
                
                // Add colspan/rowspan if specified
                if (is_array($p['colspan']) && isset($p['colspan'][$r_idx][$c_idx])) {
                    $colspan = (int)$p['colspan'][$r_idx][$c_idx];
                    $td_attr .= ' colspan="' . $colspan . '"';
                }
                if (is_array($p['rowspan']) && isset($p['rowspan'][$r_idx][$c_idx])) {
                    $rowspan = (int)$p['rowspan'][$r_idx][$c_idx];
                    $td_attr .= ' rowspan="' . $rowspan . '"';
                }

                $content = ($x < $loop_count) ? $loop[$x] : $p['trailpad'];
                
                if (!is_scalar($content)) {
                    $content = serialize($content);
                }

                // Apply custom cell formatter if provided
                if ($p['cell_format'] && is_callable($p['cell_format'])) {
                    $content = call_user_func($p['cell_format'], $content, $r_idx, $c_idx);
                }

                $output .= '<td' . ($td_attr ? ' ' . trim($td_attr) : '') . '>' . htmlspecialchars($content) . "</td>\n";
            }
            $output .= "</tr>\n";
        }
        $output .= "</tbody>\n";

        // Generate footer row if tfoot is provided
        if ($p['tfoot'] !== null && is_array($p['tfoot']) && !empty($p['tfoot'])) {
            $output .= '<tfoot';
            if ($p['tfoot_class']) {
                $output .= ' class="' . htmlspecialchars($p['tfoot_class']) . '"';
            }
            if ($p['tfoot_attr']) {
                $output .= ' ' . $p['tfoot_attr'];
            }
            $output .= '>' . "\n";
            
            $output .= '<tr>' . "\n";
            
            for ($c_idx = 0; $c_idx < $num_cols; $c_idx++) {
                $footer_content = isset($p['tfoot'][$c_idx]) ? $p['tfoot'][$c_idx] : $p['trailpad'];
                
                $tf_attr = $this->cycle('tf', $p['tf_attr'], $c_idx);
                if ($p['tf_class']) {
                    $tf_attr .= ' class="' . htmlspecialchars($p['tf_class']) . '"';
                }
                
                // Apply custom cell formatter to footer if provided
                if ($p['cell_format'] && is_callable($p['cell_format'])) {
                    $footer_content = call_user_func($p['cell_format'], $footer_content, 'footer', $c_idx);
                }
                
                $output .= '<td' . ($tf_attr ? ' ' . trim($tf_attr) : '') . '>' . htmlspecialchars($footer_content) . "</td>\n";
            }
            $output .= "</tr>\n";
            $output .= "</tfoot>\n";
        }

        $output .= "</table>\n";

        return $output;
    }

    /**
     * Cycle through attributes array or return single value
     */
    private function cycle(string $name, $var, int $no): string
    {
        if (!is_array($var)) {
            $ret = $var;
        } else {
            $count = count($var);
            $ret = ($count) ? $var[$no % $count]:null;
        }
        return ($ret) ? ' ' . $ret : '';
    }
}