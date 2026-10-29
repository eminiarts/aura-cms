<?php

namespace Aura\Base\Support;

final class EnvironmentFile
{
    public static function addGlobalAdmin(string $email): bool
    {
        $path = app()->environmentFilePath();
        $env = is_file($path) ? file_get_contents($path) : '';
        preg_match('/^AURA_GLOBAL_ADMINS=(.*)$/m', $env, $matches);
        $addresses = array_merge(
            config('aura.global_admin.emails', []),
            explode(',', trim($matches[1] ?? '', " \t\r\n\"'")),
            [$email],
        );
        $addresses = array_values(array_unique(array_filter(array_map(
            fn (string $address): string => strtolower(trim($address)), $addresses,
        ))));

        config(['aura.global_admin.emails' => $addresses]);

        return self::set('AURA_GLOBAL_ADMINS', implode(',', $addresses));
    }

    public static function set(string $key, string $value): bool
    {
        $path = app()->environmentFilePath();

        if (! is_file($path)) {
            return false;
        }

        $env = file_get_contents($path);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
        $replacement = $key.'='.$value;

        if (preg_match($pattern, $env)) {
            $env = preg_replace_callback($pattern, fn (): string => $replacement, $env);
        } else {
            $env = rtrim($env, "\r\n");
            $env = ($env === '' ? '' : $env.PHP_EOL).$replacement.PHP_EOL;
        }

        return file_put_contents($path, $env) !== false;
    }
}
