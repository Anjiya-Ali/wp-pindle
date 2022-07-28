<?php

/**
 * WP_Pindle_Sync
 *
 * @version 1.0.0
 */

class WP_Pindle_Sync {
	/**
	 * @var string
	 */
	protected $api_url = 'https://admin.pindle.dk/api/v2/';

	/**
	 * @var int
	 */
	protected $per_page = 25;

	/**
	 * @var int
	 */
	protected $page_no = 1;

	/**
	 * @var int
	 */
	protected $counter = 1;

	/**
	 * @var bool
	 */
	protected $completed = false;

	/**
	 * Cloud sale item ids
	 *
	 * @var int[]
	 */
	protected $item_ids = [];

	/**
	 * @var string
	 */
	protected $timestamp = '1441630671';

	/**
	 * @var string
	 */
	private $auth_token;

	/**
	 * @var stdClass
	 */
	private $metadata;

	public function __construct() {
		$options = get_option( WP_Pindle_Settings::SLUG, [] );
		$this->auth_token = $options['api_key'] ?? null;

		add_action( 'init', [ $this, 'every_day' ] );
	}

	public function every_day() {
		if ( empty( $this->auth_token ) ) {
			as_unschedule_all_actions( 'wp_pindle_sync' );

			return;
		}

		if ( false === as_next_scheduled_action( 'wp_pindle_sync' ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'wp_pindle_sync', [], 'wp_pindle' );
		}

		add_action( 'wp_pindle_sync', [ $this, 'sync' ] );
	}

	/**
	 * @params bool $force
	 * @return array|int[]
	 */
	public function sync( $force = false ) {
		if ( empty( $this->auth_token ) ) {
			return [];
		}

		$last_updated_ts = get_option( '_pindle_last_updated_ts' );
		if ( $last_updated_ts && ! $force ) {
			$this->timestamp = $last_updated_ts;
		}

		$this->pindle_get_metadata();

		do {
			$this->sync_events();
		} while ( ! $this->completed );

		return $this->item_ids;
	}

	protected function sync_events() {
		$args = [
			'per_page'  => $this->per_page,
			'language'  => 'da',
			'timestamp' => $this->timestamp,
			'page_no'   => $this->page_no
		];

		$query_params = http_build_query( $args );
		$url          = $this->api_url . 'events?' . $query_params;

		$req = wp_remote_request(
			$url,
			[
				'method'  => 'GET',
				'headers' => [
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'Token token=' . $this->auth_token,
				]
			]
		);

		$response_code = wp_remote_retrieve_response_code( $req );

		if ( is_wp_error( $req ) ) {
			wp_die( sprintf( '%s: (%s) %s', __METHOD__, $response_code, $req->get_error_message() ) );
		}

		$res    = wp_remote_retrieve_body( $req );
		$result = json_decode( $res );
		$events = $result->data->events ?? [];

		foreach ( $events as $event ) {
			$this->counter ++;

			$event_id = $event->id;
			$venue_id = $event->venue_id;
			$post_date = !empty( $event->created_at ) ? date( 'Y-m-d H:i:s', $event->created_at ) : date( 'Y-m-d H:i:s', time() );

			$postarr = [
				'post_title'   => $event->title,
				'post_content' => nl2br( $event->description ),
				'post_date' => $post_date,
				'post_status' => 'publish'
			];

			$post_id = $this->check_existing_event( $event_id );

			if ( empty( $post_id ) ) {
				$post_id = wp_insert_post( $postarr );
			} else {
				$postarr['ID'] = $post_id;
				$post_id       = wp_update_post( $postarr );
			}

			update_post_meta( $post_id, '_pindle_event_id', $event_id );
			update_post_meta( $post_id, '_pindle_event_venue_id', $venue_id );
			update_post_meta( $post_id, '_pindle_event_json', wp_slash( json_encode( $event ) ) );

			// Set categories
			$categories = [];

			$category = $this->get_category( $event->main_category_id );
			if ( $category ) {
				$categories[] = $category;
			}

			$parent_term = term_exists( 'events', 'category' );
			$parent_term_id = $parent_term !== 0 && $parent_term !== null ? $parent_term['term_id'] : 0;		

			foreach ( $categories as $category ) {
				$term = term_exists( $category, 'category', $parent_term_id );

				if ( $term !== 0 && $term !== null ) {
					$term_id = $term['term_id'];
					$terms = [$term_id, $parent_term_id];

					wp_set_post_terms( $post_id, $terms, 'category' );

					wp_update_term( $term_id, 'category', array( 'parent' => $parent_term_id ) );

				} else {

					$term = wp_insert_term(
						$category,
						'category',
						array(
							'parent' => $parent_term_id,
						)
					);

					if ( !is_wp_error( $term ) ) {
						$term_id = $term['term_id'];
						$terms = [$term_id, $parent_term_id];
						wp_set_post_terms( $post_id, $terms, 'category' );
					}
				}
			}

			if ( empty( $categories ) && !empty( $parent_term_id ) ) {
				wp_set_post_terms( $post_id, [$parent_term_id], 'category' );
			}

			// Set featured image
			$this->set_featured_image( $post_id, $event->images->original );

			/**
			 * Update ACF fields
			 */

			// Start date
			$dt = new DateTime();
			$dt->setTimestamp( $event->start_date );
			$start_date = $dt->format( 'Ymd' );
			update_field( 'start_date', $start_date, $post_id );

			$is_featured = get_field( 'featured_event', $post_id, false );

			if ( !empty( $is_featured ) ) {
				update_post_meta( $post_id, 'featured_event_date', $start_date );
			} else {
				delete_post_meta( $post_id, 'featured_event_date' );
			}

			// Organizer link
			$ticket_link = [
				'title'  => $event->ticket_description,
				'url'    => $event->ticket_link,
				'target' => '_blank',
			];
			update_field( 'arrangornavn', $ticket_link, $post_id );

			// Place
			$place = $this->get_city( $event->location_info );
			if ( $place ) {
				update_field( 'sted', $place, $post_id );
			}

			$when = '';
			if ( $event->start_date ) {
				$when = date_i18n( 'l ' . get_option( 'date_format' ), $event->start_date );
			}

			if ( $event->start_date && $event->end_date ) {
				$when .= ' til ';
			}

			if ( $event->end_date ) {
				$when .= date_i18n( 'l ' . get_option( 'date_format' ), $event->end_date );
			}

			update_field( 'dato', $when, $post_id );

			/**
			 * Post Expiry
			 */
			if ( $event->end_date ) {
				$opts = [
					'expireType' => 'draft',
					'id'         => $post_id
				];
				$dt   = new DateTime();
				$dt->setTimestamp( $event->end_date );
				$ts = get_gmt_from_date(
					$dt->format( 'Y' ) . '-' .
					$dt->format( 'm' ) . '-' .
					$dt->format( 'd' ) . ' ' .
					$dt->format( 'H' ) . ':' .
					$dt->format( 'i' ) . ':' .
					'0'
					,
					'U'
				);

				postexpirator_schedule_event( $post_id, $ts, $opts );
			} else {
				postexpirator_unschedule_event( $post_id );
			}

			$this->item_ids[] = $post_id;
		}

		if ( ( $result->data->total_pages <= $this->page_no ) || count( $events ) < 1 ) {
			$this->completed = true;

			update_option( '_pindle_last_updated_ts', $result->data->last_updated );

			do_action( 'wp_pindle_completed', $this->item_ids );

			foreach( $this->item_ids as $post_id ) {
				$item_id = get_post_meta( $post_id, '_pindle_event_id', true );
				$post = get_post( $post_id );

				if( empty( $item_id ) || empty( $post ) || $post->post_status !== 'future' ) continue;

				wp_update_post( [
					'ID' => $post->ID,
					'post_date' => date( 'Y-m-d H:i:s', time() ),
					'post_status' => 'publish',
				] );
			}
		} else {
			$this->page_no ++;
		}
	}

	/**
	 * @param $id int
	 *
	 * @return int
	 */
	protected function check_existing_event( $id ) {
		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"select post_id from {$wpdb->prefix}postmeta where meta_value = %d and meta_key='_pindle_event_id'",
				$id
			)
		);

		if ( empty( $post_id ) ) {
			return false;
		}

		return absint( $post_id );
	}

	/**
	 * @param int $post_id
	 * @param string $image_url
	 *
	 * Reference: https://wordpress.stackexchange.com/a/112157
	 */
	protected function set_featured_image( $post_id, $image_url ) {
		if ( has_post_thumbnail( $post_id ) ) {
			return;
		}

		// only need these if performing outside of admin environment
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// magic sideload image returns an HTML image, not an ID
		$media = media_sideload_image( $image_url, $post_id );

		// therefore we must find it so we can set it as featured ID
		if ( ! empty( $media ) && ! is_wp_error( $media ) ) {
			$args = [
				'post_type'      => 'attachment',
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'post_parent'    => $post_id
			];

			// reference new image to set as featured
			$attachments = get_posts( $args );

			if ( isset( $attachments ) && is_array( $attachments ) ) {
				foreach ( $attachments as $attachment ) {
					// grab source of full size images (so no 300x150 nonsense in path)
					$image = wp_get_attachment_image_src( $attachment->ID, 'full' );
					// determine if in the $media image we created, the string of the URL exists
					if ( strpos( $media, $image[0] ) !== false ) {
						// if so, we found our image. set it as thumbnail
						set_post_thumbnail( $post_id, $attachment->ID );
						// only want one image
						break;
					}
				}
			}
		}
	}

	/**
	 * @return false|stdClass
	 */
	public function pindle_get_metadata() {
		$args = [
			'mode'  => 'compact',
			'language'  => 'da',
			'timestamp' => '1441630671',
			'areas'   => '1'
		];

		$query_params = http_build_query( $args );
		$url          = $this->api_url . 'meta_data?' . $query_params;

		$req = wp_remote_request(
			$url,
			[
				'method'  => 'GET',
				'headers' => [
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'Token token=' . $this->auth_token,
				]
			]
		);

		if ( is_wp_error( $req ) ) {
			return false;
		}

		$res    = wp_remote_retrieve_body( $req );
		$result = json_decode( $res );

		$this->metadata = $result->data;

		return $this->metadata;
	}

	/**
	 * @param int $id
	 *
	 * @return false|string
	 */
	protected function get_category( $id ) {
		if ( empty( $this->metadata ) ) {
			return false;
		}

		foreach( $this->metadata->categories as $category ) {
			if ( $category->id === $id ) {
				return $category->name;
			}
		}

		return false;
	}

	/**
	 * @param string $location_info
	 *
	 * @return string
	 */
	protected function get_city( $location_info ) {
		$options = get_option( 'wp_pindle' );
		$place = "";
		$cities = explode( ',', $options['cities'] );

		foreach ( $cities  as $city ) {
			if ( strpos( $location_info, $city ) !== false ) {
				$place = $city;
				break;
			}
		}
		return $place;
	}
}
