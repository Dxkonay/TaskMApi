<?php

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        $current_key = 0;

        foreach ($array as $key => $noop) {
            if ($key !== $current_key) {
                return false;
            }

            ++$current_key;
        }

        return true;
    }
}

