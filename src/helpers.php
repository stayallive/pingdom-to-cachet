<?php

/**
 * Get a value from the .env file or the default if none provided.
 *
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed
 */
function env($key, $default = null)
{
    return getenv($key) ?: $default;
}

/**
 * Write a line to stdout.
 *
 * @param string $line
 */
function write($line)
{
    echo $line . PHP_EOL;
}

/**
 * Extract the ID maps from the configuration values.
 *
 * @param string $map
 *
 * @return array
 */
function extractMap($map)
{
    // Make sure there is something to map
    if (empty($map) && strpos($map, ':') !== -1) {
        return null;
    }

    list($cachet, $pingdom) = explode(':', $map);

    return compact('cachet', 'pingdom');
}
