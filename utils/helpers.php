<?php

/**
 * Global helper functions
 */

use Utils\Helper;

if (!function_exists('url')) {
    /**
     * Generate a URL with the base path.
     *
     * @param string $path The path to append to base URL
     * @return string The complete URL
     */
    function url($path = '')
    {
        return Helper::url($path);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for asset files.
     *
     * @param string $path The asset path (e.g., 'css/admin.css')
     * @return string The complete asset URL
     */
    function asset($path)
    {
        return Helper::url('assets/' . ltrim($path, '/'));
    }
}
