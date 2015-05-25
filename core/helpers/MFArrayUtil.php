<?php

class MFArrayUtil
{
    
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while(!empty($args))
        {
            $next = array_shift($args);
            foreach($next as $k => $v)
            {
                if(is_integer($k))
                    isset($res[$k]) ? $res[] = $v : $res[$k] = $v;
                elseif(is_array($v) && isset($res[$k]) && is_array($res[$k]))
                    $res[$k] = self::merge($res[$k],$v);
                else
                    $res[$k] = $v;
            }
        }
        return $res;
    }
}