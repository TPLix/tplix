<?php
/****************
 * {$.now|date_format}
 * {$.now|date_format:"%D"}
 * {$yesterday|date_format:"%A, %B %e, %Y"}
 * {$yesterday|date_format:$config.time}
 * {$yesterday|date_format:$config.date}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileDate
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        $format = isset($args[1]) ? $args[1]:'%b %e, %Y';
        $default = isset($args[2]) ? $args[2]:null;
        if (!empty($value))
        {
            // convert if it's not a valid unix timestamp
            if (preg_match('#^-?\d{1,10}$#', $value) === 0) $value = strtotime($value);
        } elseif (!empty($default))
        {
            // convert if it's not a valid unix timestamp
            $value = preg_match('#^-?\d{1,10}$#', $default) === 0 ? strtotime($default):$default;
        } else {
            return '';
        }
        // fallback if strtotime failed
        if ($value === false) return '';
        if (PHP_VERSION_ID >= 80000) return $this->byDateTime($value,$format,$default);
        return $this->byStrftime($value,$format,$default);
    }
    /**
     * @param mixed  $value
     * @param string $format
     * @param null   $default
     *
     * @return string
     */
    public function byStrftime($value="", $format = '%b %e, %Y', $default = null)
    {
        if(strpos($format, '%') === false) return date($format, $value);
        // Credits for that windows compat block to Monte Ohrt who made smarty's date_format plugin
        if (DIRECTORY_SEPARATOR == '\\') {
            $_win_from = ['%D','%h','%n','%r','%R','%t','%T'];
            $_win_to   = ['%m/%d/%y','%b',"\n",'%I:%M:%S %p','%H:%M',"\t",'%H:%M:%S'];
            if (strpos($format, '%e') !== false) {
                $_win_from[] = '%e';
                $_win_to[]   = sprintf('%\' 2d', date('j', $value));
            }
            if (strpos($format, '%l') !== false)
            {
                $_win_from[] = '%l';
                $_win_to[]   = sprintf('%\' 2d', date('h', $value));
            }
            $format = str_replace($_win_from, $_win_to, $format);
        }
        return strftime($format, $value);
    }
    public function byDateTime($value="", $format = '%b %e, %Y', $default = null)
    {
        if(strpos($format, '%') === false) return date($format, $value);
        // Mapping of strftime → PHP date() equivalents
        $map = ['%a'=>'D','%A'=>'l','%d'=>'d','%e'=>'j','%j'=>'z','%u'=>'N','%w'=>'w','%b'=>'M','%B'=>'F','%h'=>'M','%m'=>'m','%C'=>'y','%g'=>'y','%G'=>'o','%y'=>'y','%Y'=>'Y','%H'=>'H','%I'=>'h','%l'=>'g','%M'=>'i','%p'=>'A','%P'=>'a','%r'=>'h:i:s A','%R'=>'H:i','%S'=>'s','%T'=>'H:i:s','%D'=>'m/d/y','%F'=>'Y-m-d','%x'=>'Y-m-d','%X'=>'H:i:s','%n'=>"\n",'%t'=>"\t"];
        // Convert format
        $phpFormat = strtr($format, $map);
        $dt = new \DateTime();
        $dt->setTimestamp((int)$value);
        return $dt->format($phpFormat);
    }    
}