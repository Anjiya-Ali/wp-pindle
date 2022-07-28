<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 1.0.0
 */
class WP_Pindle_Settings {
	const SLUG = 'wp_pindle';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings() {
		register_setting( self::SLUG, self::SLUG, [ $this, 'validation' ] );
		register_setting( self::SLUG, "client_badge", [ $this, 'badge_validation' ] );


		add_settings_section( 'wp-pindle-main', __( 'WP Pindle', 'wp-pindle' ), null, self::SLUG );
		add_settings_field(
			'api_key',
			__( 'Pindle API Key', 'wp-pindle' ),
			[ $this, 'field_api_key' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'api_key',
				'class'     => 'regular-text',
			]
		);

		add_settings_field(
			'pull_events',
			__( 'Pull New Events', 'wp-pindle' ),
			[ $this, 'field_pull_events' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'pull_events'
			]
		);

		add_settings_field(
			'cities',
			__( 'Pindle Cities', 'wp-pindle' ),
			[ $this, 'field_cities' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'cities',
			]
		);

		add_settings_field(
			'client_text',
			__( 'Text for Client Events', 'wp-pindle' ),
			[ $this, 'client' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'client_text',
			]
		);

		add_settings_field(
			'other_text',
			__( 'Text for Other Events', 'wp-pindle' ),
			[ $this, 'other' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'other_text',
			]
		);

		add_settings_field(
			'client_badge',
			__( 'Badge for client events', 'wp-pindle' ),
			[ $this, 'badge' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'client_badge',
			]
		);

		add_settings_field(
			'Venue_id',
			__( 'Venue ID for Vierviborg Events', 'wp-pindle' ),
			[ $this, 'venue_id' ],
			self::SLUG,
			'wp-pindle-main',
			[
				'label_for' => 'venue_id',
				'class'     => 'regular-text',
			]
		);

		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	public function page() {
		add_menu_page(
			__( 'WP Pindle', 'wp-pindle' ),
			__( 'WP Pindle', 'wp-pindle' ),
			'manage_options',
			self::SLUG,
			[ $this, 'setup' ],
			WP_PINDLE_URL . 'assets/img/pindle.png'
		);
	}

	public function admin_notices() {
		settings_errors( 'wp_pindle_errors' );
	}

	public function setup() {
		include_once 'admin/templates/main.php';
	}

	/**
	 * @param array $input
	 *
	 * @return mixed
	 */
	public function validation( array $input ) {
		return $input;
	}

	public function badge_validation( $input ) {
		return $input;
	}

	/**
	 * @param array $args
	 */
	public function field_api_key( array $args ) {
		$options = get_option( 'wp_pindle' );
		$value   = sanitize_text_field( $options[ $args['label_for'] ] ?? '' );
		echo '<input type="text" autocomplete="off" value="' . $value . '" class="' . $args['class'] . '" name="wp_pindle[' . $args['label_for'] . ']" id="' . $args['label_for'] . '">';
	}

	/**
	 * @param array $args
	 */
	public function field_cities( array $args ) {
		$options = get_option( 'wp_pindle' );
		$value   = sanitize_text_field( $options[ $args['label_for'] ] ?? '' );
		echo '<textarea name="wp_pindle[' . $args['label_for'] . ']" id="' . $args['label_for'] . '" rows="5" cols="54" type="textarea">' . $value . '</textarea>';
	}

	/**
	 * @param array $args
	 */
	public function field_pull_events( array $args ) {
		$link = add_query_arg( [
			'pindle_action' => 'fetch_events',
			'_nonce'        => wp_create_nonce( 'pindle_action_fetch_events' ),
		] );

		$link2 = add_query_arg( [
			'pindle_action' => 'fetch_force_events',
			'_nonce'        => wp_create_nonce( 'pindle_action_fetch_force_events' ),
		] );

		printf(
			'<a href="%s" class="button button-secondary">%s</a>&nbsp;<a href="%s" class="button button-secondary">%s</a>',
			$link,
			__( 'Fetch', 'wp-pindle' ),
			$link2,
			__( 'Force Fetch', 'wp-pindle' ),
		);
	}

	public function client( array $args ) {
		$options = get_option( 'wp_pindle' );
		$value   = sanitize_text_field( $options[ $args['label_for'] ] ?? '' );
		echo '<textarea name="wp_pindle[' . $args['label_for'] . ']" id="' . $args['label_for'] . '" rows="5" cols="54" type="textarea">' . $value . '</textarea>';
	}

	public function other( array $args ) {
		$options = get_option( 'wp_pindle' );
		$value   = sanitize_text_field( $options[ $args['label_for'] ] ?? '' );
		echo '<textarea name="wp_pindle[' . $args['label_for'] . ']" id="' . $args['label_for'] . '" rows="5" cols="54" type="textarea">' . $value . '</textarea>';
	}

	public function badge( array $args ) {
		$url = get_option( 'client_badge' );
		if ( isset( $_POST['client_badge'] ) and $_POST['client_badge'] != $url ) {
			update_option( 'client_badge', $_POST['client_badge'] );
		}
		?>
        <div class='image-preview-wrapper'>
            <img id='image-preview' height='100' src=<?php echo get_option( 'client_badge' ) ?>>
        </div>
        <input id="upload_image_button" type="button" class="button" value="<?php _e( 'Upload image' ); ?>"/>
		<?php
		echo '<input type="hidden" name="client_badge" id="' . $args['label_for'] . '" value="' . get_option( 'client_badge' ) . '">';
	}

	public function venue_id( array $args ) {
		$options = get_option( 'wp_pindle' );
		$value   = sanitize_text_field( $options[ $args['label_for'] ] ?? '' );
		echo '<input type="text" name="wp_pindle[' . $args['label_for'] . ']" id="' . $args['label_for'] . '" class="' . $args['class'] . '" value="' . $value . '">';
	}
}

add_action( 'admin_footer', 'media_selector_print_scripts' );


function media_selector_print_scripts() {

	$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id', 0 );
	ob_start();

	?>
    <script type='text/javascript'>

        jQuery(document).ready(function ($) {

            // Uploading files
            var file_frame;
            var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
            var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this

            jQuery('#upload_image_button').on('click', function (event) {

                event.preventDefault();

                // If the media frame already exists, reopen it.
                if (file_frame) {
                    // Set the post ID to what we want
                    file_frame.uploader.uploader.param('post_id', set_to_post_id);
                    // Open frame
                    file_frame.open();
                    return;
                } else {
                    // Set the wp.media post id so the uploader grabs the ID we want when initialised
                    wp.media.model.settings.post.id = set_to_post_id;
                }

                // Create the media frame.
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select a image to upload',
                    button: {
                        text: 'Use this image',
                    },
                    multiple: false	// Set to true to allow multiple files to be selected
                });

                // When an image is selected, run a callback.
                file_frame.on('select', function () {
                    // We set multiple to false so only get one image from the uploader
                    attachment = file_frame.state().get('selection').first().toJSON();

                    // Do something with attachment.id and/or attachment.url here
                    $('#image-preview').attr('src', attachment.url).css('width', 'auto');
                    $('#client_badge').attr('value', attachment.url).css('width', 'auto');
                    $('#image_attachment_id').val(attachment.id);

                    // Restore the main post ID
                    wp.media.model.settings.post.id = wp_media_post_id;
                });

                // Finally, open the modal
                file_frame.open();
            });

            // Restore the main ID when the add media button is pressed
            jQuery('a.add_media').on('click', function () {
                wp.media.model.settings.post.id = wp_media_post_id;
            });
        });

    </script><?php

}
