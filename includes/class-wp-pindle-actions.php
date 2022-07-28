<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 1.0.0
 */
class WP_Pindle_Actions {
	public function __construct() {
		add_action( 'init', [ $this, 'actions' ] );
	}

	public function actions() {
		if ( ! is_admin() ) {
			return;
		}

		$action = filter_input( INPUT_GET, 'pindle_action', FILTER_SANITIZE_STRING );

		if ( ! $action ) {
			return;
		}

		if ( method_exists( $this, $action ) ) {
			$nonce = filter_input( INPUT_GET, '_nonce', FILTER_SANITIZE_STRING );

			if ( ! wp_verify_nonce( $nonce, 'pindle_action_' . $action ) ) {
				wp_die( __( 'Invalid security nonce', 'wp-pindle' ) );
			}

			$this->{$action}();
		}
	}

	public function fetch_events( $force = false ) {
		$item_ids = wp_pindle()->sync_api->sync( $force );
		$notices  = [];

		if ( empty( $item_ids ) ) {
			$notices['warning'] = __( 'There were no new events', 'wp-pindle' );
		} else {
			$notices['success'] = sprintf(
				_n( '%d event was updated to the system', '%d events were updated to the system', count( $item_ids ), 'wp-pindle' ),
				count( $item_ids )
			);
		}

		set_transient( '_pindle_notices', $notices, MINUTE_IN_SECONDS );

		wp_redirect( remove_query_arg( [ 'pindle_action', '_nonce' ] ) );
		exit;
	}

	public function fetch_force_events() {
		$this->fetch_events( true );
	}
}
