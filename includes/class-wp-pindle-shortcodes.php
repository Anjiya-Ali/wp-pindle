<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$posts_list = array();

/**
 * @since 1.0.0
 */
class WP_Pindle_Shortcodes {
	public function __construct() {
		add_shortcode( 'wp_pindle_events', [ $this, 'pindle_events' ] );
		add_action( 'wp_ajax_pindle_load_more_events', [ $this, 'pindle_load_more_events' ] );
		add_action( 'wp_ajax_nopriv_pindle_load_more_events', [ $this, 'pindle_load_more_events' ] );
		add_action( 'save_post', [ $this, 'pindle_save_event_start_date' ], 10, 1 );
	}


	/**
	 * Auto Order Management
	 *
	 * @return string
	 */
	public function pindle_events() {

		ob_start();

		wp_enqueue_style( 'wp-pindle-css', WP_PINDLE_URL . '/assets/css/wp-pindle.css' );

		$event_id = filter_input( INPUT_GET, 'ps_view', FILTER_VALIDATE_INT );
		$event    = $event_id ? get_post( $event_id ) : null;
		if ( $event ) {
			include WP_PINDLE_DIR . '/templates/shortcodes/pindle-single-event.php';
		} else {

			$filters = [
				'ps'                   => filter_input( INPUT_GET, 'ps', FILTER_SANITIZE_STRING ),
				'cat'                  => filter_input( INPUT_GET, 'ps_cat', FILTER_SANITIZE_STRING ),
				'date'                 => filter_input( INPUT_GET, 'ps_date', FILTER_SANITIZE_STRING ),
				'place'                => filter_input( INPUT_GET, 'ps_where', FILTER_SANITIZE_STRING ),
				'feature_event'        => filter_input( INPUT_GET, 'ps_fe_event', FILTER_SANITIZE_STRING ),
				'other_event'          => filter_input( INPUT_GET, 'ps_ot_event', FILTER_SANITIZE_STRING ),
				'feature_hidden_event' => filter_input( INPUT_GET, 'ps_fe_event_hid', FILTER_SANITIZE_STRING )
			];

			$events = $this->events( 1, $filters );

			include WP_PINDLE_DIR . '/templates/shortcodes/pindle-events.php';
		}


		return ob_get_clean();
	}


	/**
	 * @return array
	 */
	private function events( $paged, $filters ) {
		$venue_id = '';
		$options  = get_option( 'wp_pindle' );
		$value    = sanitize_text_field( $options['venue_id'] ?? '' );

		if ( $value ) {
			$venue_id = $value;
		}

		$total = 0;

		$search       = $filters['ps'];
		$cat          = $filters['cat'];
		$date         = $filters['date'];
		$place        = $filters['place'];
		$fe_event     = $filters['feature_event'];
		$ot_event     = $filters['other_event'];
		$fe_event_hid = $filters['feature_hidden_event'];

		$args = [
			'post_status'    => 'publish',
			'posts_per_page' => 9,
			'paged'          => $paged,
			'tax_query'      => [
				[
					'taxonomy' => 'category',
					'field'    => 'name',
					'terms'    => 'EVENTS',
				]
			],
			'meta_query'     => [
				'relation' => 'AND',
				[
					'relation'                  => 'OR',
					'pindle_event_venue_id'     => [
						'key'     => 'venue_id',
						'value'   => $venue_id,
						'compare' => '=',
					],
					'pindle_event_start_date'   => [
						'key'     => 'start_date',
						'type'    => 'NUMERIC',
						'compare' => 'EXISTS',
					],
					'is_featured_event'         => [
						'key'     => 'featured_event_date',
						'type'    => 'NUMERIC',
						'compare' => 'EXISTS'
					],
					'is_other_featured_event'   => [
						'key'     => 'featured_event_date',
						'type'    => 'NUMERIC',
						'compare' => 'NOT EXISTS'
					],
					'is_other_event'            => [
						'key'     => '_pindle_event_id',
						'compare' => 'NOT EXISTS',
					],
					'pindle_event_not_venue_id' => [
						'key'     => 'venue_id',
						'value'   => $venue_id,
						'compare' => '!=',
					],
				],
			],
			'orderby'        => [
				'is_featured_event'         => 'DESC',
				'pindle_event_venue_id'     => 'DESC',
				'pindle_event_not_venue_id' => 'DESC',
				'is_other_featured_event'   => 'DESC',
				'is_other_event'            => 'DESC',
			]
		];

		if ( $search ) {
			$args = [
				's' => $search
			];
		}

		if ( $cat ) {
			$args['tax_query'][] = [
				'taxonomy' => 'category',
				'field'    => 'name',
				'terms'    => $cat
			];
		}

		if ( $place ) {
			$args['meta_query'][] = [
				'key'     => 'sted',
				'value'   => $place,
				'compare' => '='
			];
		}

		if ( $date ) {
			$args['meta_query'][] = [
				'key'     => 'start_date',
				'compare' => '<=',
				'value'   => $date,
				'type'    => 'numeric',
			];

			$timestamp            = DateTime::createFromFormat( 'Ymd', $date )->getTimestamp();
			$args['meta_query'][] = [
				'key'     => '_expiration-date',
				'compare' => '>=',
				'value'   => $timestamp,
				'type'    => 'numeric',
			];
		}

		add_filter( 'posts_results', [ $this, 'featured_events_results' ], 999, 2 );

		if ( empty( $fe_event_hid ) ) {
			$fe_event = "on";
		}

		if ( $fe_event == "on" && $ot_event != "on" ) {
			unset( $args['meta_query'][0]['is_other_event'] );
			unset( $args['meta_query'][0]['pindle_event_not_venue_id'] );
		} else if ( $fe_event != "on" && $ot_event == "on" ) {
			unset( $args['meta_query'][0]['pindle_event_venue_id'] );
			unset( $args['meta_query'][0]['is_featured_event'] );
			unset( $args['meta_query'][0]['is_other_featured_event'] );
		}
		if ( $fe_event != "on" && $ot_event != "on" ) {
			$total = 0;
		} else {
			$posts       = new WP_Query( $args );
			$events_args = $args;
			unset( $events_args['paged'] );
			$events_args['posts_per_page'] = - 1;

			$events_q   = new WP_Query( $events_args );
			$posts_list = $events_q->posts ?? [];

			foreach ( $posts_list as $pl_key => $post_single ) {
				$featured_event = get_post_meta( $post_single->ID, 'featured_event', true );
				$other_event    = '';
				$json           = get_post_meta( $post_single->ID, '_pindle_event_json', true );
				$other          = json_decode( $json );
				if ( $other ) {
					$other_event = $other->venue_id;
				}

				if ( ( $featured_event ) and ( $other_event == $venue_id ) ) {
					$total ++;
				} else if ( ( $featured_event ) and ( ( ! $other ) || $other_event !== $venue_id ) and $ot_event == "on" ) {
					$total ++;
				} else if ( ( $other_event == $venue_id ) ) {
					$total ++;
				} else if ( ( $ot_event == "on" ) && ( ( ! $other ) || $other_event !== $venue_id ) && ( ! $featured_event ) ) {
					$total ++;
				}
			}

		}

		$posts_list = $posts->posts ?? [];

		$sorted_events   = [];
		$featured_events = [];
		$other_events    = [];

		foreach ( $posts_list as $pl_key => $post_single ) {
			$featured_event = get_post_meta( $post_single->ID, 'featured_event', true );
			$other_event    = '';
			$json           = get_post_meta( $post_single->ID, '_pindle_event_json', true );
			$other          = json_decode( $json );
			if ( $other ) {
				$other_event = $other->venue_id;
			}
			if ( ( $featured_event ) and ( $other_event == $venue_id ) ) {
				$client_featured_events[ $pl_key ] = $post_single;
			} else if ( ( $featured_event ) and ( ( ! $other ) || $other_event !== $venue_id ) and $ot_event == "on" ) {
				$featured_events[ $pl_key ] = $post_single;
			} else if ( ( $other_event == $venue_id ) ) {
				$sorted_events[ $pl_key ] = $post_single;
			} else if ( ( $ot_event == "on" ) && ( ( ! $other ) || ( $other_event !== $venue_id ) ) && ( ! $featured_event ) ) {
				$other_events[ $pl_key ] = $post_single;
			}

		}

		$events = [];

		if ( ! empty ( $client_featured_events ) ) {
			$events = array_merge( $events, $client_featured_events );
		}
		if ( ! empty ( $sorted_events ) ) {
			$events = array_merge( $sorted_events );
		}
		if ( ! empty ( $featured_events ) ) {
			$events = array_merge( $events, $featured_events );
		}
		if ( ! empty ( $other_events ) ) {
			$events = array_merge( $events, $other_events );
		}

		return [
			'data'    => $events,
			'total'   => $total,
			'filters' => [
				'search'        => $search,
				'cat'           => $cat,
				'date'          => $date,
				'place'         => $place,
				'feature_event' => $fe_event,
				'other_event'   => $ot_event,
			]
		];
	}

	/**
	 * Reference: https://wordpress.stackexchange.com/a/138794/36349
	 *
	 * @param stdClass $posts
	 * @param WP_Query $query
	 *
	 * @return array
	 */
	public function featured_events_results( $posts, $query ) {
		remove_filter( 'posts_reults', [ $this, 'featured_events_results' ], 999 );

		$featured    = [];
		$nonfeatured = [];

		foreach ( $posts as $post ) {
			if ( get_post_meta( $post->ID, 'featured_event', true ) ) {
				$featured[] = $post;
			} else {
				$nonfeatured[] = $post;
			}
		}


		$posts = array_merge( $featured, $nonfeatured );

		return $posts;
	}

	/**
	 * Load more events ajax callback
	 */
	function pindle_load_more_events() {
		$html  = '';
		$paged = intval( $_POST['events_page_num'] );

		$filters = [
			'ps'            => '',
			'cat'           => $_POST['ps_cat'],
			'date'          => $_POST['ps_date'],
			'place'         => $_POST['ps_where'],
			'feature_event' => $_POST['ps_fe_event'],
			'other_event'   => $_POST['ps_ot_event']
		];

		$events = $this->events( $paged, $filters );

		ob_start();

		foreach ( $events['data'] as $event ) {

			$other = get_post_meta( $event->ID, '_pindle_event_id', true );

			if ( ! $other ) {
				$val     = __( "Other Events", 'wp-pindle' );
				$options = get_option( 'wp_pindle' );
				$para    = $options['other_text'];
			}
			break;
		}

		if ( count( $events['data'] ) > 0 ) {
			?>
            <h1 class="heading"><?php if ( $val )
					echo $val ?></h1>
            <p class="paragraph"><?php if ( $para )
					echo $para ?></p>

			<?php
			foreach ( $events['data'] as $event ) {
				include WP_PINDLE_DIR . '/templates/shortcodes/pindle-events-single-content.php';
			}
			$html = ob_get_clean();
		}


		echo $html;

		wp_die();
	}

	function pindle_save_event_start_date( $post_id ) {
		// do not save if this is an auto save routine
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// save fields
		if ( ! empty( $_POST['acf'] ) ) {

			$fields         = $_POST['acf'];
			$featured_event = ! empty( $fields['field_607dde322cba7'] ) ? intval( $fields['field_607dde322cba7'] ) : 0;
			$start_date     = get_field( 'start_date', $post_id, false );

			if ( $featured_event === 1 && ! empty( $start_date ) ) {
				update_post_meta( $post_id, 'featured_event_date', $start_date );
			} else {
				delete_post_meta( $post_id, 'featured_event_date' );
			}

		}

		// return
		return $post_id;
	}
}
