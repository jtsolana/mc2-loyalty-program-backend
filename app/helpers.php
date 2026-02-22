<?php

if (! function_exists('hashids_encode')) {
    function hashids_encode(int|string $value): string
    {
        return app('hashids')->encode($value);
    }
}

if (! function_exists('hashids_decode')) {
    function hashids_decode(string $value): int|string|null
    {
        $decoded = app('hashids')->decode($value);

        return $decoded[0] ?? null;
    }
}
