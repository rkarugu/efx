<?php

if (!function_exists('asset_public')) {
    function asset_public($path, $secure = null)
    {
        return app('url')->asset($path, $secure);
    }
}
