<?php

if (!function_exists('configure_app_timezone')) {
    function configure_app_timezone(): void
    {
        $timezone = trim((string)(getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur'));
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'Asia/Kuala_Lumpur';
        }

        date_default_timezone_set($timezone);
    }
}

configure_app_timezone();
