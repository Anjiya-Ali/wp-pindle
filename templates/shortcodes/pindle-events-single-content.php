<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var WP_Post $event
 */

$cats = ( get_the_category( $event->ID ) );
$cat  = array_filter( $cats, function ( $cat ) {
	return $cat->name === "EVENTS";
} );

$is_event_cat = count( $cat ) > 0;
$other        = get_post_meta( $event->ID, 'venue_id', true );

$venue_id = '';
$options = get_option( 'wp_pindle' );
$value   = sanitize_text_field( $options['venue_id'] ?? '' );

if($value){
    $venue_id=$value;
}

$url  = parse_url( $_SERVER['REQUEST_URI'] );
$path = $url['path'];


$custom              = [];
$thumb               = get_the_post_thumbnail_url( $event->ID, 'full' );
$json                = get_post_meta( $event->ID, '_pindle_event_json', true );
$event_data          = json_decode( $json );
$start_date          = $event_data->start_date ?? false;
$end_date            = $event_data->end_date ?? false;
$location            = $event_data->location_info ?? '';
$other               = $event_data->venue_id ?? '';
$event_time          = wp_pindle_get_event_duration( $start_date, $end_date );
$featured_event_date = get_post_meta( $event->ID, 'featured_event_date', true );
$item_classes        = ! empty( $featured_event_date ) ? 'event__item event_featured' : 'event__item';
$event_class         = '';
?>
<div class="<?php echo $item_classes; ?>">
	<?php
	if ( get_post_meta( $event->ID, 'featured_event', true ) ) {
		$event_class = 'event_feature';
	} else if ( $is_event_cat && $other==$venue_id ) {
		$event_class = 'event_client';
	}
    $badge_url=get_option('client_badge');
	?>

    <div class="event__item_wrap <?php echo $event_class ?>">
        <div class="event__item_image"
             style="background-image: url('<?php echo $thumb; ?>');">
        </div>
        <?php if ( $is_event_cat && $other==$venue_id && ($badge_url)) : ?>
            <div class="badge"
             style="background-image: url(<?php echo $badge_url; ?>)">            
            </div>
		<?php endif; ?>
        <div class="event__item_info">
            <div class="event__item_title">
                <h3><?php echo $event->post_title; ?></h3>
            </div>
			<?php if ( $location ) : ?>
                <div class="event__item_location">
					<?php echo $location; ?>
                </div>
			<?php endif; ?>
            <div class="event__item_description">
				<?php echo get_the_excerpt( $event ); ?>
            </div>
            <div class="event__item_link">
                <a href="<?php echo add_query_arg( [ 'ps_view' => $event->ID ], $path ); ?>">
                    <span class="event__item_link_text"><?php _e( 'View more', 'wp-pindle' ); ?></span>
                    <span class="event__item_link_icon">&#x279C;</span>
                </a>
            </div>
        </div>
		<?php
		$start_date = get_field( 'start_date', $event->ID, false );
		if ( $start_date ) :
			?>
            <div class="event__item_date_info">
				<?php
				$timestamp     = DateTime::createFromFormat( 'Ymd', $start_date )->getTimestamp();
				$expiration_ts = get_post_meta( $event->ID, '_expiration-date', true );
				?>
                <div class="event__item_date">
                    <div class="event__item_date_day">
						<?php echo date_i18n( 'd', $timestamp ); ?>
                    </div>
                    <div class="event__item_date_month">
						<?php echo date_i18n( 'F', $timestamp ); ?>
                    </div>
                </div>
				<?php if ( $expiration_ts ) : ?>
                    <span class="event__item_date_separator">—</span>
					<?php if ( $event_time ) : ?>
                        <div class="date__interval">
                            <span class="svg-wrap">
                                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 26 26" focusable="false" tabindex="-1"><g
                                            data-name="Group 8810"><path
                                                d="M13 24a11 11 0 1111-11 11.012 11.012 0 01-11 11zm0-20a9 9 0 109 9 9.01 9.01 0 00-9-9z"
                                                data-name="Ellipse 24"></path><path
                                                d="M14.71 15.839H7.721v-2h4.989V6.305h2v9.534z"
                                                data-name="Path 987"></path></g></svg>
                            </span>
                            <span>
                                <?php echo $event_time; ?>
                            </span>
                        </div>
					<?php else: ?>
                        <div class="event__item_date">
                            <div class="event__item_date_day">
								<?php echo date_i18n( 'd', $expiration_ts ); ?>
                            </div>
                            <div class="event__item_date_month">
								<?php echo date_i18n( 'F', $expiration_ts ); ?>
                            </div>
                        </div>
					<?php endif; ?>
				<?php endif; ?>
            </div>
		<?php endif; ?>
    </div>
</div>