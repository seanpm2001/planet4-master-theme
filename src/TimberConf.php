<?php

declare(strict_types=1);

namespace P4\MasterTheme;

class TimberConf
{
    public static function hooks(): void {
        add_filter('timber/post/classmap', function ($classmap) {
            $custom_classmap = [
                'page' => Post::class,
                'post' => Post::class,
                //'tag' => Post::class,
                'campaign' => Post::class,
                'p4-page-type' => Post::class,
            ];

            return array_merge($classmap, $custom_classmap);
        });

        add_filter('timber/user/class', function ($class, \WP_User $user) {
            return User::class;
        }, 10, 2);

        add_filter('timber/twig/environment/options', function ($options) {
            $options['autoescape'] = 'html';
            $options['cache'] = defined('WP_DEBUG') && is_bool(WP_DEBUG)
                ? !WP_DEBUG : true;
            return $options;
        });
    }
}
