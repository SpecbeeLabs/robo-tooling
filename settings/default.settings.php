<?php

// phpcs:ignoreFile

/**
 * @file
 * Drupal site-specific configuration file.
 */

/**
 * Database settings:
 *
 * The $databases array specifies the database connection or
 * connections that Drupal may use.  Drupal is able to connect
 * to multiple databases, including multiple types of databases,
 * during the same request.
 */
 $databases = [];


/**
 * Location of the site configuration files.
 */
$settings['config_sync_directory'] = '../config/sync';

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = '';

/**
 * Deployment identifier.
 *
 * Drupal's dependency injection container will be automatically invalidated and
 * rebuilt when the Drupal core version changes. When updating contributed or
 * custom code that changes the container, changing this identifier will also
 * allow the container to be invalidated as soon as code is deployed.
 */
# $settings['deployment_identifier'] = \Drupal::VERSION;

/**
 * Access control for update.php script.
 */
$settings['update_free_access'] = FALSE;

/**
 * Fallback to HTTP for Update Manager and for fetching security advisories.
 */
# $settings['update_fetch_with_http_fallback'] = TRUE;

/**
 * External access proxy settings:
 */
# $settings['http_client_config']['proxy']['http'] = 'http://proxy_user:proxy_pass@example.com:8080';
# $settings['http_client_config']['proxy']['https'] = 'http://proxy_user:proxy_pass@example.com:8080';
# $settings['http_client_config']['proxy']['no'] = ['127.0.0.1', 'localhost'];

/**
 * Reverse Proxy Configuration:
 */
# $settings['reverse_proxy'] = TRUE;

/**
 * Specify every reverse proxy IP address in your environment.
 * This setting is required if $settings['reverse_proxy'] is TRUE.
 */
# $settings['reverse_proxy_addresses'] = ['a.b.c.d', ...];

/**
 * Reverse proxy trusted headers.
 *
 * Sets which headers to trust from your reverse proxy.
 *
 * Common values are:
 * - \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR
 * - \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST
 * - \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT
 * - \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO
 * - \Symfony\Component\HttpFoundation\Request::HEADER_FORWARDED
 *
 */
# $settings['reverse_proxy_trusted_headers'] = \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO | \Symfony\Component\HttpFoundation\Request::HEADER_FORWARDED;


/**
 * Page caching:
 */
# $settings['omit_vary_cookie'] = TRUE;

/**
 * Class Loader.
 *
 * If the APCu extension is detected, the classloader will be optimized to use
 * it. Set to FALSE to disable this.
 *
 * @see https://getcomposer.org/doc/articles/autoloader-optimization.md
 */
# $settings['class_loader_auto_detect'] = FALSE;

/**
 * Default mode for directories and files written by Drupal.
 *
 * Value should be in PHP Octal Notation, with leading zero.
 */
$settings['file_chmod_directory'] = 0775;
$settings['file_chmod_file'] = 0664;

/**
 * Public file path:
 *
 * A local file system path where public files will be stored. This directory
 * must exist and be writable by Drupal. This directory must be relative to
 * the Drupal installation directory and be accessible over the web.
 */
$settings['file_public_path'] = $site_path . '/files';

/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
$settings['file_private_path'] = $site_path . '/files/private';

/**
 * Temporary file path:
 *
 * A local file system path where temporary files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * If this is not set, the default for the operating system will be used.
 *
 * @see \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory()
 */
$settings['file_temp_path'] = '/tmp';

/**
 * A custom theme for the offline page:
 *
 * This applies when the site is explicitly set to maintenance mode through the
 * administration page or when the database is inactive due to an error.
 * The template file should also be copied into the theme. It is located inside
 * 'core/modules/system/templates/maintenance-page.html.twig'.
 *
 * Note: This setting does not apply to installation and update pages.
 */
# $settings['maintenance_theme'] = 'bartik';

/**
 * PHP settings:
 *
 * To see what PHP settings are possible, including whether they can be set at
 * runtime (by using ini_set()), read the PHP documentation:
 * http://php.net/manual/ini.list.php
 * See \Drupal\Core\DrupalKernel::bootEnvironment() for required runtime
 * settings and the .htaccess file for non-runtime settings.
 * Settings defined there should not be duplicated here so as to avoid conflict
 * issues.
 */

/**
 * If you encounter a situation where users post a large amount of text, and
 * the result is stripped out upon viewing but can still be edited, Drupal's
 * output filter may not have sufficient memory to process it.  If you
 * experience this issue, you may wish to uncomment the following two lines
 * and increase the limits of these variables.  For more information, see
 * http://php.net/manual/pcre.configuration.php.
 */
# ini_set('pcre.backtrack_limit', 200000);
# ini_set('pcre.recursion_limit', 200000);

/**
 * Configuration overrides.
 *
 * To globally override specific configuration values for this site,
 * set them here. You usually don't need to use this feature. This is
 * useful in a configuration file for a vhost or directory, rather than
 * the default settings.php.
 *
 */
# $config['system.site']['name'] = 'My Drupal site';
# $config['user.settings']['anonymous'] = 'Visitor';

/**
 * Fast 404 pages:
 *
 * Drupal can generate fully themed 404 pages. However, some of these responses
 * are for images or other resource files that are not displayed to the user.
 * This can waste bandwidth, and also generate server load.
 *
 * Remove the leading hash signs if you would like to alter this functionality.
 */
$config['system.performance']['fast_404']['exclude_paths'] = '/\/(?:styles)|(?:system\/files)\//';
$config['system.performance']['fast_404']['paths'] = '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
$config['system.performance']['fast_404']['html'] = '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * Override the default service container class.
 *
 * This is useful for example to trace the service container for performance
 * tracking purposes, for testing a service container with an error condition or
 * to test a service container that throws an exception.
 */
# $settings['container_base_class'] = '\Drupal\Core\DependencyInjection\Container';

/**
 * Trusted host configuration.
 *
 * Drupal core can use the Symfony trusted host mechanism to prevent HTTP Host
 * header spoofing.
 *
 * To enable the trusted host mechanism, you enable your allowable hosts
 * in $settings['trusted_host_patterns']. This should be an array of regular
 * expression patterns, without delimiters, representing the hosts you would
 * like to allow.
 */
# $settings['trusted_host_patterns'] = [
#   '^www\.example\.com$',
# ];

/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * The default number of entities to update in a batch process.
 */
$settings['entity_update_batch_size'] = 50;

/**
 * Entity update backup.
 */
$settings['entity_update_backup'] = TRUE;

/**
 * Node migration type.
 *
 * This is used to force the migration system to use the classic node migrations
 * instead of the default complete node migrations. The migration system will
 * use the classic node migration only if there are existing migrate_map tables
 * for the classic node migrations and they contain data. These tables may not
 * exist if you are developing custom migrations and do not want to use the
 * complete node migrations. Set this to TRUE to force the use of the classic
 * node migrations.
 */
$settings['migrate_node_migrate_type_classic'] = FALSE;

/**
 * Load local development override configuration, if available.
 *
 */
#
if (getenv('LANDO') === 'ON' && file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

/**
 * Include the migration settings.
 */
if (file_exists(DRUPAL_ROOT . '/sites/default/migration.settings.php')) {
  include DRUPAL_ROOT . '/sites/default/migration.settings.php';
}

/**
 * Configure the config split based on environments.
 */
# $config['config_split.config_split.dev']['status'] = FALSE;
# $config['config_split.config_split.stage']['status'] = FALSE;
