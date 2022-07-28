<div class="wrap" id="wp-pindle-main">
	<h1><?php _e( 'WP Pindle', 'wp-pindle' ); ?></h1>
	<?php settings_errors(); 
		wp_enqueue_media();
	?>
	
	<form action="options.php" method="post"  enctype=”multipart/form-data”>
		<?php settings_fields( WP_Pindle_Settings::SLUG );?>
		<?php do_settings_sections( WP_Pindle_Settings::SLUG ); ?>
		<?php submit_button(); ?>
	</form>
</div>
