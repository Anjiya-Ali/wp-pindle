<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var WP_Post $event
 */

$thumb                    = get_the_post_thumbnail_url( $event->ID, 'full' );
$json                     = get_post_meta( $event->ID, '_pindle_event_json', true );
$event_data               = json_decode( $json );
$location_info            = $event_data->location_info ?? '';
$opening_hours            = $event_data->opening_hours ?? false;
$opening_hours_deviations = $event_data->opening_hours_deviations ?? false;
$start_date               = $event_data->start_date ?? false;
$end_date                 = $event_data->end_date ?? false;
$event_time               = wp_pindle_get_event_duration( $start_date, $end_date );

$ticket_link = get_field( 'arrangornavn', $event->ID );
?>
<div class="event__wrap_single">
    <div class="event__single">
        <div class="event__info event__col">
            <h2><?php echo get_the_title( $event ); ?></h2>

            <div class="event__metainfo">
                <div class="event__metainfo_item">
                    <div class="event__check_icon"><i class="fa fa-calendar-check-o fa-fw" aria-hidden="true"></i></div>
                    <div class="event__metainfo_description"><?php echo get_field( 'dato', $event->ID ); ?></div>
                </div>
				<?php if ( $event_time ) : ?>
                    <div class="event__metainfo_item">
                        <div class="event__check_icon"><i class="fa fa-clock-o fa-fw" aria-hidden="true"></i></div>
                        <div class="event__metainfo_description"><?php echo $event_time; ?></div>
                    </div>
				<?php endif; ?>
				<?php if ( $location_info ) : ?>
                    <div class="event__metainfo_item">
                        <div class="event__check_icon"><i class="fa fa-map-marker fa-fw" aria-hidden="true"></i></div>
                        <div class="event__metainfo_description"><?php echo $location_info; ?></div>
                    </div>
				<?php endif; ?>
				<?php if ( ! empty( $ticket_link ) ) : ?>
                    <div class="event__metainfo_item">
                        <div class="event__check_icon"><i class="fa fa-info-circle fa-fw" aria-hidden="true"></i></div>
                        <div class="event__metainfo_description"><?php echo $ticket_link['title']; ?></div>
                    </div>
                    <div class="event__metainfo_item">
                        <div class="event__check_icon"><i class="fa fa-link fa-fw" aria-hidden="true"></i></div>
                        <div class="event__metainfo_description">
                            <a href="<?php echo esc_url( $ticket_link['url'] ); ?>"
                               title="<?php echo esc_attr( $ticket_link['title'] ); ?>"
                               target="<?php echo esc_attr( $ticket_link['target'] ); ?>"
                            >
								<?php _e( 'Buy ticket here', 'wp-pindle' ); ?>
                            </a>
                        </div>
                    </div>
				<?php endif; ?>
            </div>

            <div class="event__col">
                <div class="event__image" style="background-image: url('<?php echo $thumb; ?>');"></div>
            </div>
            <div class="event__item_description">
				<?php echo apply_filters( 'the_content', $event->post_content ); ?>
            </div>
        </div>
    </div>

	<?php if ( $opening_hours || $opening_hours_deviations ) : ?>
        <div class="event__hours">
			<?php if ( $opening_hours ) : ?>
                <div class="event__opening_hours event__oh_col">
                    <h4><?php _e( 'Opening Hours', 'wp-pindle' ); ?></h4>

					<?php foreach ( $event_data->opening_hours as $opening_hour ) : ?>
                        <div class="event__opening_hour_day">
							<?php
							$days = array_map( function ( $day ) {
								return wp_pindle_get_week_days( $day );
							}, $opening_hour->days );
							echo implode( ' &#8901; ', $days );

							echo ' &nbsp; ' . DateTime::createFromFormat( 'Hi', $opening_hour->open->start )->format( 'H:i' );
							echo ' &dash; ' . DateTime::createFromFormat( 'Hi', $opening_hour->open->end )->format( 'H:i' );
							?>
                        </div>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>

			<?php if ( $opening_hours_deviations ) : ?>
                <div class="event__opening_hours_d event__oh_col">
                    <h4><?php _e( 'Exceptions', 'wp-pindle' ); ?></h4>

					<?php foreach ( $opening_hours_deviations as $opening_hour ) : ?>
                        <div class="event__opening_hour_day">
							<?php
							$timestamp = DateTime::createFromFormat( 'Y-m-d', $opening_hour->date )->getTimestamp();
							echo date_i18n( get_option( 'date_format' ), $timestamp );

							if ( ! $opening_hour->open ) {
								echo ' &nbsp; ' . __( 'Closed', 'wp-pindle' );
							} else {
								echo ' &nbsp; ' . DateTime::createFromFormat( 'Hi', $opening_hour->open->start )->format( 'H:i' );
								echo ' &dash; ' . DateTime::createFromFormat( 'Hi', $opening_hour->open->end )->format( 'H:i' );
							}
							?>
                        </div>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>
        </div>
	<?php endif; ?>
</div>
