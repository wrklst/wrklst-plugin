<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Base;

class Deactivate
{
    public static function exec() {
        flush_rewrite_rules();
    }
}
