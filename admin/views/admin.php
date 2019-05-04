<?php
/**
 * Represents the view for the administration dashboard.
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 * @package		DPIorDie
 * @author		Cory Null(Noll) Crimmins - Golden <cory190@live.com>
 * @license		GPL-3.0+
 * @link			http://wordpress.org/plugins
 * @copyright	2014 Cory Null(Noll) Crimmins - Golden
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="POST">
		<?php
				settings_fields("dpi_or_die_settings");
				do_settings_sections("default");
				wp_nonce_field('dpi_or_die_set', 'dpi_or_die_nonce');
				submit_button();
		?>
	</form>
</div>
