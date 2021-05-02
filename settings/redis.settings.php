<?php

/**
 * @file
 * Redis configuration.
 */

// Include the Redis services.yml file. Adjust the path if you installed to a contrib or other subdirectory.
$settings['container_yamls'][] = 'modules/redis/example.services.yml';
$conf['redis_client_host'] = 'cache';

//PhpRedis should be build in the application container.
$settings['redis.connection']['interface'] = 'PhpRedis';
// Check variables if present else fallback to default.
$settings['redis.connection']['host'] = $_ENV['CACHE_HOST'] ?? 'cache';
$settings['redis.connection']['port'] = $_ENV['CACHE_PORT'] ?? '6379';

$settings['redis_compress_length'] = 100;
$settings['redis_compress_level'] = 1;

$settings['cache']['default'] = 'cache.backend.redis'; // Use Redis as the default cache.
$settings['cache_prefix']['default'] = 'specbee-redis';

$settings['cache']['bins']['form'] = 'cache.backend.database'; // Use the database for forms
