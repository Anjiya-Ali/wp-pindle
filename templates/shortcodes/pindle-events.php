<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$metadata = wp_pindle()->sync_api->pindle_get_metadata();

$categories = $metadata->categories ?? [];
$dates      = [];
$places     = wp_pindle_get_places();
/**
 * @var array $events
 */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<?php
    $val="";
    $para="";
    $fe_checked = "undefined";
    $feature_val = isset($_GET['ps_fe_event']) ? "true" : "false";

    if( isset($_GET['ps_fe_event'])) {
        $fe_checked='checked';
    }
    else if (isset($_GET['ps_fe_event_hid']) and ($_GET['ps_fe_event_hid'] =="false" or $_GET['ps_fe_event_hid'] =="true")) {
        $fe_checked ='unchecked';
    } 
   
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<div class="event__found">
    <?php $reset_url=site_url().'/detsker'; ?>
	<?php printf( _n( 'We have found <strong><span class="event__found_num">%d</span></strong> event', 'We have found <strong><span class="event__found_num">%d</span></strong> events', $events['total'], 'wp-pindle' ), $events['total'] ); ?>
    <button class="reset" onclick="location.href='<?php echo $reset_url; ?>'" type="button"><?php _e( 'RESET FILTERS', 'wp-pindle' ); ?></button>
</div>

<div class="event__wrap_list">
    <div id="pindle_event__filters">
        <input type="checkbox" />

        <!--
		Some spans to act as a hamburger.

		They are acting like a real hamburger,
		not that McDonalds stuff.
		-->
        <span></span>
        <span></span>
        <span></span>

        <form action="" method="get" class="event__search">
            <div class="form-field-text sky-list-wrap__form-field sky-list-wrap__form-field--text">
                <div class="form-field-text__wrap">
                    <input class="form-field-text__input" type="text"
                           placeholder="<?php _e( 'I am looking for...', 'wp-pindle' ); ?>" name="ps"
                           value="<?php echo esc_attr( $events['filters']['search'] ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19.577 19.577"
                         focusable="false" tabindex="-1" class="form-field-text__icon">
                        <g fill="none" stroke="#004536" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2">
                            <circle cx="7.434" cy="7.434" r="7.434" stroke="none"></circle>
                            <circle cx="7.434" cy="7.434" r="6.434"></circle>
                        </g>
                        <path fill="none" stroke="#004536" stroke-linecap="round" stroke-miterlimit="10"
                              stroke-width="2"
                              d="M12.84 12.84l5.322 5.322"></path>
                    </svg>
                </div>
            </div>
            <div class="form-field-dropdown sky-list-wrap__form-field sky-list-wrap__form-field--dropdown">
                <select class="form-field-dropdown__dropdown" name="ps_cat" id="ps_cat">
                    <option value=""><?php _e( 'All categories', 'wp-pindle' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo $category->name; ?>" <?php selected( $events['filters']['cat'], $category->name ); ?>>
                            <?php echo $category->name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14.364 8.135"
                     focusable="false" tabindex="-1" class="form-field-dropdown__icon">
                    <path data-name="Path 261" d="M1.273 1.273l5.856 5.962 5.962-5.962" fill="none" stroke="#004536"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                </svg>
            </div>
            <div class="form-field-dropdown sky-list-wrap__form-field sky-list-wrap__form-field--datepicker">
                <div class="form-field-text__wrap">
                    <input class="form-field-text__input" type="text"
                           placeholder="<?php _e( 'All dates', 'wp-pindle' ); ?>" name="ps_date" id="ps_date"
                           value="<?php echo esc_attr( $events['filters']['date'] ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14.364 8.135"
                         focusable="false" tabindex="-1" class="form-field-dropdown__icon">
                        <path data-name="Path 261" d="M1.273 1.273l5.856 5.962 5.962-5.962" fill="none" stroke="#004536"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                    </svg>
                </div>
            </div>
            <div class="form-field-dropdown sky-list-wrap__form-field sky-list-wrap__form-field--dropdown">
                <select class="form-field-dropdown__dropdown" name="ps_where" id="ps_where">
                    <option value=""><?php _e( 'Where', 'wp-pindle' ); ?></option>
                    <?php foreach ( $places as $place ) : ?>
                        <option value="<?php echo $place; ?>" <?php selected( $events['filters']['place'], $place ); ?>>
                            <?php echo $place; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14.364 8.135"
                     focusable="false" tabindex="-1" class="form-field-dropdown__icon">
                    <path data-name="Path 261" d="M1.273 1.273l5.856 5.962 5.962-5.962" fill="none" stroke="#004536"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                </svg>
            </div>

            <div class="form-field-dropdown sky-list-wrap__form-field sky-list-wrap__form-field--dropdown">
                <div class="checkbox checkbox2">
                    <label class="text" for="ps_fe_event"><?php _e( 'Selected Events', 'wp-pindle' ); ?></label>
                    <input class="check check2" id="ps_fe_event" name="ps_fe_event"
                           type=checkbox <?php if((($fe_checked) and $fe_checked=="checked") or $fe_checked=="undefined")
                           echo "checked = 'checked'";?>>
                </div>
                <input type = "hidden"  id="ps_fe_event_hid" name="ps_fe_event_hid" value = <?php echo $feature_val;?>>
            </div>

            <div class="form-field-dropdown sky-list-wrap__form-field sky-list-wrap__form-field--dropdown">
                <div class="checkbox checkbox3">
                    <label class="text" for="ps_ot_event"><?php _e( 'Other Events', 'wp-pindle' ); ?></label>
                    <input class="check check3" id="ps_ot_event" name="ps_ot_event"
                           type=checkbox <?php if ( isset( $_GET['ps_ot_event'] ) ) {
                        echo "checked='checked'";
                    } ?>>
                </div>
            </div>
            <script>
                (function () {
                    'use strict';

                    const $form = document.querySelector('#pindle_event__filters form');

                    $form.querySelectorAll('input[type="text"]').forEach(($input) => {
                        $input.addEventListener("keyup", ({key}) => {
                            if (key === "Enter") {
                                $form.submit()
                            }
                        })
                    });

                    $form.querySelectorAll('select').forEach(($select) => {
                        $select.addEventListener('change', () => {
                            $form.submit();
                        })
                    });

                    $form.querySelectorAll('input[type="checkbox"]').forEach(($checkbox) => {
                        $checkbox.addEventListener('change', () => {
                            $form.submit();
                        })
                    });

                    const resizeFilterSlide = function() {
                        if (!window.matchMedia("(max-width: 768px)").matches) return;
                        const offset = document.querySelector('.qodef-container-inner').offsetLeft;
                        document.querySelector('.event__search').style.marginLeft = `-${offset}px`;
                    };
                    resizeFilterSlide();
                    window.addEventListener('resize', resizeFilterSlide);

                    flatpickr('.sky-list-wrap__form-field--datepicker input', {
                        dateFormat: 'Ymd',
                        altFormat: '<?php echo get_option( 'date_format' ); ?>',
                        altInput: true,
                        disableMobile: 'true',
                        onChange: () => {
                            $form.submit();
                        }
                    });
                })();
            </script>
        </form>
    </div>

    <div class="event__list">
       <?php foreach ( $events['data'] as $event ){
            $venue_id = '';
            $options = get_option( 'wp_pindle' );
            $value   = sanitize_text_field( $options['venue_id'] ?? '' );
    
            if($value){
                $venue_id=$value;
            }
            $id = '';
            $json = get_post_meta($event->ID,'_pindle_event_json',true);
            $other = json_decode( $json );
            if($other){
                $id = $other->venue_id;
            }
            if(($id==$venue_id)){
                $val=__("Client Events",'wp-pindle');
                $options = get_option( 'wp_pindle' );
                $para = $options['client_text'] ?? '';
            }
            else if((!$other) || $id!==$venue_id){
               $val=__("Other Events",'wp-pindle');
               $options = get_option( 'wp_pindle' );
               $para = $options['other_text'] ?? '';
            }
            break;
       }
       ?>
        <h1 class="heading"><?php echo $val ?></h1>
        <p class="paragraph"><?php echo $para ?></p>

		<?php foreach ( $events['data'] as $event ) : ?>
			<?php include WP_PINDLE_DIR . '/templates/shortcodes/pindle-events-single-content.php'; ?>
		<?php endforeach; ?>
    </div>
    <div class="event_load_more_wrap">
        <span id="events_page_num">2</span>
        <a href="#" id="events_load_more"
           class="qodef-btn qodef-btn-medium qodef-btn-solid qodef-btn-icon qodef-btn-animated" data-status="active">
            <span class="spinner-grow spinner-grow-sm events-btn-loading" role="status" aria-hidden="true"></span>
            <span class="qodef-btn-text"><?php _e( 'View more', 'wp-pindle' ); ?></span>
            <span class="qodef-icon-holder"><i class="qodef-icon-font-awesome fa fa-arrow-down"></i></span>
        </a>
    </div>
</div>

<!-- Load more ajax -->
<script>
    jQuery(document).ready(function ($) {

        $('#events_load_more').click(function (e) {
            e.preventDefault();
            const $loadMoreBtn = $("#events_load_more");
            const btnStatus = $loadMoreBtn.attr('data-status');

            if (btnStatus === "inactive") return false;

            $loadMoreBtn.attr('data-status', 'inactive');

            const pageNum = $('#events_page_num').text();
            const psCat = $('#ps_cat').val();
            const psDate = $('#ps_date').val();
            const psWhere = $('#ps_where').val();

            const psfeev = $("#ps_fe_event").is(":checked") ? "on" : "off";
            const psotev = $("#ps_ot_event").is(":checked") ? "on" : "off";

            const ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

            const data = {
                'action': 'pindle_load_more_events',
                'events_page_num': pageNum,
                'ps_cat': psCat,
                'ps_date': psDate,
                'ps_where': psWhere,
                'ps_fe_event': psfeev,
                'ps_ot_event': psotev
            };

            $.post(ajaxurl, data, function (response) {
                if (response.length) {
                    $(".event__list").append(response);
                    var newPageNum = parseInt(pageNum) + 1;
                    $('#events_page_num').text(newPageNum);
                } else {
                    $("#events_load_more").hide();
                }
                setTimeout(
                    function () {
                        $("#events_load_more").attr('data-status', 'active');
                    }, 2000);
            });

        });

    });
</script>