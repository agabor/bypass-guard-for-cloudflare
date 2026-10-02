<?php
/**
 * Plugin Name: Bypass Guard for Cloudflare
 * Plugin URI: https://github.com/agabor/bypass-guard-for-cloudflare
 * Description: Blocks requests that bypass Cloudflare by requiring a secret token header. For hosts that offer no stronger origin protection.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Gabor Angyal
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bypass-guard-for-cloudflare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BYPASS_GUARD_VERSION', '1.0.0' );
define( 'BYPASS_GUARD_PLUGIN_FILE', __FILE__ );
define( 'BYPASS_GUARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BYPASS_GUARD_HEADER_NAME', 'BYPASS-GUARD-TOKEN' );
define( 'BYPASS_GUARD_LOG_LIMIT', 500 );

require_once BYPASS_GUARD_PLUGIN_DIR . 'includes/class-bypass-guard-activator.php';
require_once BYPASS_GUARD_PLUGIN_DIR . 'includes/class-bypass-guard-logger.php';
require_once BYPASS_GUARD_PLUGIN_DIR . 'includes/class-bypass-guard-plugin.php';
require_once BYPASS_GUARD_PLUGIN_DIR . 'includes/class-bypass-guard-settings-page.php';

register_activation_hook( __FILE__, array( 'Bypass_Guard_Activator', 'activate' ) );

Bypass_Guard_Plugin::init();