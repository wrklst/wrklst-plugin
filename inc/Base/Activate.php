<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Base;

class Activate
{
    public static function exec() {
        flush_rewrite_rules();
    }
}
