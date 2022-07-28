<?php

/**
 * @return array
 */
function wp_pindle_get_places() {
	global $wpdb;

	$query = "SELECT meta_value
				FROM {$wpdb->prefix}postmeta
				WHERE meta_key = 'sted'
				  AND meta_value != ''
				  AND meta_value IS NOT NULL
				GROUP BY meta_value";

	$results = $wpdb->get_col( $query );

	return $results;
}

function wp_pindle_get_week_days( int $day ) {
	$days = [
		0 => __( 'Sunday' ),
		1 => __( 'Monday' ),
		2 => __( 'Tuesday' ),
		3 => __( 'Wednesday' ),
		4 => __( 'Thursday' ),
		5 => __( 'Friday' ),
		6 => __( 'Saturday' ),
	];

	return $days[ $day ];
}

/**
 * @param int $start_date
 * @param int $end_date
 */
function wp_pindle_get_event_duration( $start_date, $end_date ) {
	$event_time = '';

	if ( $start_date && $end_date ) {
		$start_date_dt  = ( new DateTime() )->setTimestamp( $start_date );
		$end_date_dt    = ( new DateTime() )->setTimestamp( $end_date );
		$start_end_diff = $start_date_dt->diff( $end_date_dt );

		if ( $start_end_diff->invert === 0 && $start_end_diff->days === 0 ) {
			$timezone = wp_timezone_string();
			$sdt      = ( new DateTime() )->setTimestamp( $start_date );
			$sdt->setTimezone( new DateTimeZone( $timezone ) );
			$edt = ( new DateTime() )->setTimestamp( $end_date );
			$edt->setTimezone( new DateTimeZone( $timezone ) );
			$event_time = $sdt->format( 'H:i' );
			$event_time .= ' - ' . $edt->format( 'H:i' );
		}
	}

	return $event_time;
}

function wp_pindle_manage_post_posts_columns( $columns ) {
	return array_merge( $columns, [ 'wp_pindle_event_type' => __( 'Event Type', 'wp-pindle' ) ] );
}
add_filter( 'manage_post_posts_columns', 'wp_pindle_manage_post_posts_columns' );

function wp_pindle_manage_post_posts_custom_column( $column_key, $post_id ) {
	if ( $column_key !== 'wp_pindle_event_type' ) {
		return;
	}

	$name2="";
	$cat      = ( get_the_category($post_id ) );
	$name     = $cat[0]->name;
	if(count($cat)>1)
		$name2     = $cat[1]->name;
	
	$venue_id = '';
	$options = get_option( 'wp_pindle' );
	$value   = sanitize_text_field( $options['venue_id'] ?? '' );

	if($value){
		$venue_id=$value;
	}

	$id='';
	$featured = get_post_meta( $post_id, 'featured_event', true );
	$json = get_post_meta($post_id,'_pindle_event_json',true);
	$other = json_decode( $json );
	if($other){
		$id = $other->venue_id;
	}

	if ( ( $featured ) && ($name == "EVENTS" || $name2 == "EVENTS" ) && ($id==$venue_id) ) {
		_e( 'Client and Featured', 'wp-pindle' );
	} else if ( ( $featured ) && ($name == "EVENTS" || $name2 == "EVENTS" )  && ((!$other) || $id!==$venue_id ) ) {
		_e( 'Other and Featured', 'wp-pindle' );
	} else if ( ( ! $featured ) && ($name == "EVENTS" || $name2 == "EVENTS" ) && ($id==$venue_id)) {
		_e( 'Client', 'wp-pindle' );
	} else if ( ( ! $featured ) && ($name == "EVENTS" || $name2 == "EVENTS" )  && ((!$other) || $id!==$venue_id )) {
		_e( 'Other', 'wp-pindle' );
	}
}
add_action( 'manage_post_posts_custom_column', 'wp_pindle_manage_post_posts_custom_column', 10, 2 );
