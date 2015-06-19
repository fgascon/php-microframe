<?php

class MFHtml
{
    
    public static function encode($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, MF::app()->charset);
    }
    
    public static function decode($text)
    {
        return htmlspecialchars_decode($text,ENT_QUOTES);
    }
}