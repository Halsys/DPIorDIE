<?php
/**
 * DPIorDie class. This class should ideally be used to work with the public-
 * facing side of the WordPress site. If you're interested in introducing
 * administrative or dashboard functionality, then refer to `class-dpi-or-die-
 * admin.php`
 * @package		DPIorDie
 * @author		Cory Null(Noll) Crimmins - Golden <cory190@live.com>
 * @license		GPL-3.0+
 * @link			http://wordpress.org/plugins
 * @copyright	2014 Cory Null(Noll) Crimmins - Golden
*/
class DPIorDie {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 * @since	1.0.0
	 * @var		string
	 */
	const VERSION = "0.0.1";

	/**
	 * Unique identifier for your plugin.
	 * The variable name is used as the text domain when internationalizing
	 * strings of text. Its value should match the Text Domain file header in the
	 * main plugin file.
	 * @since	1.0.0
	 * @var		string
	 */
	protected $plugin_slug = "dpi-or-die";

	/**
	 * Instance of this class.
	 * @since	1.0.0
	 * @var		object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 * @since	1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( "init", array( $this, "load_plugin_textdomain" ) );

	}

	/**
	 * Return the plugin slug.
	 * @since		1.0.0
	 * @return	string	plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 * @since		1.0.0
	 * @return	object	A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 * @since	1.0.0
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network
	 * Activate" action, false if WPMU is disabled or plugin is activated on an
	 * individual blog.
	 */
	public static function activate( $network_wide ) {

		$result = add_role(
			"Uploader",
			__("Uploader"),
			array(
				"switch_themes" => 0,
				"edit_themes" => 0,
				"activate_plugins" => 0,
				"edit_plugins" => 0,
				"edit_users" => 0,
				"edit_files" => 0,
				"manage_options" => 0,
				"moderate_comments" => 0,
				"manage_categories" => 0,
				"manage_links" => 0,
				"upload_files" => 1,
				"import" => 0,
				"unfiltered_html" => 0,
				"edit_posts" => 0,
				"edit_others_posts" => 0,
				"edit_published_posts" => 0,
				"publish_posts" => 0,
				"edit_pages" => 0,
				"read" => 0,
				"level_10" => 0,
				"level_9" => 0,
				"level_8" => 0,
				"level_7" => 0,
				"level_6" => 0,
				"level_5" => 0,
				"level_4" => 0,
				"level_3" => 0,
				"level_2" => 0,
				"level_1" => 0,
				"level_0" => 0,
				"edit_others_pages" => 0,
				"edit_published_pages" => 0,
				"publish_pages" => 0,
				"delete_pages" => 0,
				"delete_others_pages" => 0,
				"delete_published_pages" => 0,
				"delete_posts" => 0,
				"delete_others_posts" => 0,
				"delete_published_posts" => 0,
				"delete_private_posts" => 0,
				"edit_private_posts" => 0,
				"read_private_posts" => 0,
				"delete_private_pages" => 0,
				"edit_private_pages" => 0,
				"read_private_pages" => 0,
				"delete_users" => 0,
				"create_users" => 0,
				"unfiltered_upload" => 1,
				"edit_dashboard" => 0,
				"update_plugins" => 0,
				"delete_plugins" => 0,
				"install_plugins" => 0,
				"update_themes" => 0,
				"install_themes" => 0,
				"update_core" => 0,
				"list_users" => 0,
				"remove_users" => 0,
				"add_users" => 0,
				"promote_users" => 0,
				"edit_theme_options" => 0,
				"delete_themes" => 0,
				"export" => 0,
				"edit_comment" => 0,
				"approve_comment" => 0,
				"unapprove_comment" => 0,
				"reply_comment" => 0,
				"quick_edit_comment" => 0,
				"spam_comment" => 0,
				"unspam_comment" => 0,
				"trash_comment" => 0,
				"untrash_comment" => 0,
				"delete_comment" => 0,
				"edit_permalink" => 0,
			)
		);

		add_filter(
			'upload_size_limit',
			array("DPIorDie", 'filter_upload_size')
		);

	}

	/**
	 * Fired when the plugin is deactivated.
	 * @since	1.0.0
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network
	 * Deactivate" action, false if WPMU is disabled or plugin is deactivated on
	 * an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		remove_filter(
			'upload_size_limit',
			array("DPIorDie", 'filter_upload_size')
		);

	}

	/**
	 * Returns the minimum size of either the entered value or what is allowed.
	 * @since	1.0.0
	 * @param	boolean	the size being tested.
	 */
	public static	function filter_upload_size( $size ) {
		$default = wp_max_upload_size();
		$option = get_option(
			"max_upload_size",
			$default
		);

		if ( get_site_option( 'upload_space_check_disabled' ) ) {
				return min($option, $size );
		}

		return min($option, $size, get_upload_space_available() );
	}

	/**
	 * Load the plugin text domain for translation.
	 * @since	1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( "plugin_locale", get_locale(), $domain );
		$moFile =
			trailingslashit(WP_LANG_DIR) .
			$domain . "/" .
			$domain . "-" .
			$locale . ".mo";
		load_textdomain(
			$domain,
			$moFile
		);
		load_plugin_textdomain(
			$domain,
			FALSE,
			basename(
				plugin_dir_path(
					dirname(__FILE__)
				)
			) . "/languages/"
		);
	}

}
