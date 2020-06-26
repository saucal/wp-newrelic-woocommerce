<?php
/**
 * Plugin Name: New Relic Reporting for WooCommerce
 * Description: New Relic APM reports for WordPress. Requires "New Relic Reporting for WordPress" by 10up
 * Version:     1.2
 * Author:      SAU/CAL
 * Author URI:  https://saucal.com
 * License:     GPLv2 or later
 * Text Domain: wp-newrelic-woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_NewRelic_WooCommerce {

	public function __construct() {
		add_filter( 'wp_nr_transaction_name', array( $this, 'set_transaction_name' ) );
		add_action( 'init', array( $this, 'set_user_type' ) );
		add_filter( 'wp_nr_app_name', array( $this, 'separate_cron' ) );
	}

	public function set_transaction_name( $transaction ) {
		if( isset( $_REQUEST['wc-ajax'] ) ) {
			$action = $_REQUEST['wc-ajax'];
			$transaction = "wc-ajax/{$action}";
		} else if( isset( $_REQUEST['gform_submit'] ) ) {
			$form_id = $_REQUEST['gform_submit'];
			$transaction = "gform/{$form_id}";
		}
		return $transaction;
	}

	public function set_user_type() {
		if( ! is_user_logged_in() ) {
			$this->add_custom_parameter( 'user_type', 'logged-out' );
		} else {
			if( ! current_user_can( 'edit_posts' ) ) {
				$this->add_custom_parameter( 'user_type', 'logged-in' );
			} else {
				$this->add_custom_parameter( 'user_type', 'admin' );
			}
		}
	}

	/**
	 * Adds a custom parameter through `newrelic_add_custom_parameter`
	 * Prefixes the $key with 'wcnr_' to avoid collisions with NRQL reserved words
	 *
	 * @see https://docs.newrelic.com/docs/agents/php-agent/configuration/php-agent-api#api-custom-param
	 *
	 * @param $key      string  Custom parameter key
	 * @param $value    string  Custom parameter value
	 * @return bool
	 */
	public function add_custom_parameter( $key, $value ) {
		if ( function_exists( 'newrelic_add_custom_parameter' ) ) {
			//prefixing with wcnr_ to avoid collisions with reserved works in NRQL
			$key = 'wcnr_' . $key;
			return newrelic_add_custom_parameter( $key, apply_filters( 'wc_nr_add_custom_parameter', $value, $key ) );
		}

		return false;
	}

	public function separate_cron( $name ) {
		$req_type = false;
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$req_type = 'cron';
		} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
			$req_type = 'cli';
		} elseif ( defined( 'WP_GEARS' ) && WP_GEARS ) {
			$req_type = 'gearman';
		}

		return $req_type !== false ? $name . " " . $req_type : $name;
	}

}
new WP_NewRelic_WooCommerce();