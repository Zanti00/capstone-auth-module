<?php

namespace App\Helpers;

class CookieHelper
{
    private static function secureAuthCookies(): bool
    {
        return (bool) config('session.secure', app()->environment('production'));
    }

    /**
     * Create a standard authentication cookie with secure and Strict SameSite attributes.
     *
     * @param string $name
     * @param string $value
     * @param int $minutes
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public static function makeAuthCookie(string $name, string $value, int $minutes)
    {
        return cookie(
            $name,
            $value,
            $minutes,
            null, // path
            null, // domain
            self::secureAuthCookies(), // Secure
            true, // HttpOnly
            false, // raw
            'Strict' // SameSite
        );
    }

    /**
     * Create an expired cookie with the exact same attributes to ensure it is properly deleted by the browser.
     *
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public static function forgetAuthCookie(string $name)
    {
        return cookie(
            $name,
            '',
            -2628000, // Expire far in the past
            null, // path
            null, // domain
            self::secureAuthCookies(), // Secure
            true, // HttpOnly
            false, // raw
            'Strict' // SameSite
        );
    }
}
