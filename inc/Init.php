<?php
/**
* @package WrkLstPlugin
*/
namespace Inc;

final class Init
{
    public static function get_services() {
        return [
            Pages\Admin::class,
            Base\Enqueue::class,
            Base\SettingsLinks::class,
            Ajax\AjaxHandler::class,
            PostTypes\Biography::class,
            PostTypes\Artist::class,
            PostTypes\Exhibition::class,
            PostTypes\ArtFair::class,
            Shortcodes\BiographyShortcode::class,
            Webhooks\BiographyWebhook::class,
        ];
    }

    public static function register_services()
    {
        foreach(self::get_services() as $class)
        {
            $service = self::instantiate($class);
            if(method_exists($service,'register'))
            {
                $service->register();
            }
        }
    }

    private static function instantiate($class)
    {
        return new $class();
    }
}
