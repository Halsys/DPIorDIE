<?php
/**
 * DPIorDie
 * @package		DPIorDie_Admin
 * @author		Cory Null(Noll) Crimmins - Golden <cory190@live.com>
 * @license		GPL-3.0+
 * @link			http://wordpress.org/plugins
 * @copyright	2014 Cory Null(Noll) Crimmins - Golden
 */

global $wp;

function logAsJSON($arg) {
	file_put_contents('php://stderr', $arg . "\n");
}

/**
 * DPIorDie_Admin class. This class should ideally be used to work with the
 * administrative side of the WordPress site. If you're interested in
 * introducing public-facing functionality, then refer to `class-plugin-
 * name.php`
 * @package	DPIorDie_Admin
 * @author	Cory Null(Noll) Crimmins - Golden <cory190@live.com>
 */
class DPIorDie_Admin {
	// DO NOT EVER CHANGE THESE...
	protected static $dropboxClientId = "052cwiia22r8agc";
	protected static $dropboxSecret = "r61hsz79vvn4wmt";

	/**
	 * Instance of this class.
	 * @since	1.0.0
	 * @var		object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 * @since	1.0.0
	 * @var		string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 * @since		1.0.0
	 * @return	void
	 */
	private function __construct() {

		if( ! is_super_admin() ) {
			return;
		}

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = DPIorDie::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Add the options page and menu item.
		add_action(
			"admin_menu",
			array($this, "add_plugin_admin_menu")
		);

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename(
			plugin_dir_path(realpath(dirname(__FILE__))) .
			$this->plugin_slug .
			".php"
		);

		add_filter(
			"plugin_action_links_" .
			$plugin_basename,
			array($this, "add_action_links")
		);

		add_filter(
			"admin_init",
			array($this, "admin_init")
		);

		add_filter(
			"wp_handle_upload_prefilter",
			array($this, "admin_upload_filter")
		);

	}

	/**
	*	Filters the uploads made on the admin page.
	* @since 1.0.0
	* @return void
	*/
	function admin_upload_filter($file) {
		$current_user = wp_get_current_user();
		if ($current_user && $current_user->has_cap("upload_files")) {
			$type = $file["type"];
			$size = $file["size"];
			$tmpFile = $file["tmp_name"];
			$imageTypes = array(
				"image/jpeg",
				"image/png",
				"image/bmp",
				"image/gif",
				"image/tiff",
				"image/pipeg",
				"image/psd",
				"image/webp"
			);
			if(in_array($type, $imageTypes, false)) {
				$loadedFile = fopen($tmpFile, "r")
					or die("Unable to open file!");
				$exif = exif_read_data($loadedFile);
				if(	isset($exif->XResolution) &&
						isset($exif->YResolution)) {
					$xResString = $exif["XResolution"];
					$yResString = $exif["YResolution"];
					$xIndex = stripos($xResString, "/");
					$xIndex = stripos($yResString, "/");
					$xRes =
						floatval(substr($xResString, 0, $xIndex)) / floatval(substr($xResString, $xIndex + 1));
					$yRes =
						floatval(substr($yResString, 0, $xIndex)) / floatval(substr($yResString, $xIndex + 1));
					$maxDPISize = floatval($this::get_max_dpi_size());
					$minDPISize = floatval($this::get_min_dpi_size());
					if($xRes > $maxDPISize || $yRes > $maxDPISize) {
						$file["error"] =
							"DPI is too large (" . $xRes . ", " . $yRes . "), " .
							$maxDPISize . " is the set maximum.";
						return $file;
					}
					if($xRes < $minDPISize || $yRes < $minDPISize) {
						$file["error"] =
							"DPI is too small (" . $xRes . ", " . $yRes . "), " .
							$minDPISize . " is the set minimum.";
						return $file;
					}
				}
				fclose($loadedFile);
				$this::upload_file($file);
			}
		}
		return $file;
	}

	/**
	* Initialize the administrative settings page.
	* @since	1.0.0
	* @return	void
	*/
	function admin_init() {
		/// Generic ///

		// Section group
		add_settings_section(
			'dpi_or_die_settings',
			'Basic options',
			array($this, 'render_settings_preface'),
			'default'
		);

		// Add settings
		add_settings_field(
			"max_upload_size",
			"Max upload size (MB)",
			array($this, 'render_max_upload_size_field'),
			'default',
			'dpi_or_die_settings'
		);

		add_settings_field(
			"min_dpi_size",
			"Minimum DPI size",
			array($this, "render_min_dpi_size_field"),
			"default",
			"dpi_or_die_settings"
		);

		add_settings_field(
			"max_dpi_size",
			"Maximum DPI size",
			array($this, "render_max_dpi_size_field"),
			"default",
			"dpi_or_die_settings"
		);

		// Regester settings
		register_setting('default', 'max_upload_size');
		register_setting("default", 'min_dpi_size');
		register_setting("default", 'max_dpi_size');

		/// Dropbox ///

		// Section group
		add_settings_section(
			"dropbox_settings",
			"Dropbox",
			array($this, "render_dropbox_preface"),
			"default"
		);

		// Add settings
		add_settings_field(
			"dropbox_code",
			"Dropbox authorization code",
			array($this, "render_dropbox_code_field"),
			"default",
			"dropbox_settings"
		);

		add_settings_field(
			"dropbox_token",
			"Dropbox access token",
			array($this, "render_dropbox_token_field"),
			"default",
			"dropbox_settings"
		);

		add_settings_field(
			"upload_to_dropbox",
			"Upload to dropbox",
			array($this, "render_upload_to_dropbox_field"),
			"default",
			"dropbox_settings"
		);

		add_settings_field(
			"dropbox_folder_path",
			"Dropbox upload destination path",
			array($this, "render_dropbox_folder_path_field"),
			"default",
			"dropbox_settings"
		);

		// Regester settings
		register_setting("default", "dropbox_code");
		register_setting("default", "dropbox_token");
		register_setting("default", "upload_to_dropbox");
		register_setting("default", "dropbox_folder_path");
	}

	/**
	 * Returns the dropbox user id.
	 * @since		1.0.0
	 * @return	string
	*/
	static function dropbox_user_id() {
		return get_option("dropbox_user_id", null);
	}

	/**
	 * Returns the dropbox code.
	 * @since		1.0.0
	 * @return	string
	*/
	static function dropbox_code() {
		return get_option("dropbox_code", null);
	}

	/**
	 * Returns the dropbox token.
	 * @since		1.0.0
	 * @return	string
	*/
	static function dropbox_token() {
		return get_option("dropbox_token", null);
	}

	/**
	 * Returns boolean to if dropbox is allowed or not.
	 * @since		1.0.0
	 * @return	boolean
	*/
	static function upload_to_dropbox() {
		return get_option("upload_to_dropbox", false);
	}

	/**
	 * Returns the dropbox folder path.
	 * @since		1.0.0
	 * @return	string
	*/
	static function dropbox_folder_path() {
		return get_option("dropbox_folder_path", "/");
	}

	/**
	 * Returns the min dpi size for a file.
	 * @since		1.0.0
	 * @return	number
	*/
	static function min_dpi_size() {
		return get_option("min_dpi_size", 72);
	}

	/**
	 * Returns the max dpi size for a file.
	 * @since		1.0.0
	 * @return	number
	*/
	static function max_dpi_size() {
		return get_option("max_dpi_size", 300);
	}

	/**
	 * Returns the max file size.
	 * @since		1.0.0
	 * @return	number
	*/
	static function max_file_size() {
		return get_option(
			"max_upload_size",
			wp_max_upload_size()
		) / 1000000;
	}

	/**
	 * Gets the dropbox token with a authorization code.
	 * @since		1.0.0
	 * @return	void
	*/
	static function get_dropbox_token() {
		$authURL = "https://api.dropboxapi.com/oauth2/token";
		$authToken = DPIorDie_Admin::dropbox_token();
		$authCode = DPIorDie_Admin::dropbox_code();
		$authID = DPIorDie_Admin::$dropboxClientId;
		$dropboxSecret = DPIorDie_Admin::$dropboxSecret;
		$authorization = "Bearer " . $authToken;
		if ($authToken) {
			return $authToken;
		}
		$headers = array(
			"Authorization"	=>	"Basic " . base64_encode($authID . ":" . $dropboxSecret),
			"Accept"				=>	"application/json;ver=1.0",
		);
		$body = array(
			"code"					=>	$authCode,
			"grant_type"		=>	"authorization_code",
			// "client_id"			=>	$authID,
			// "client_secret"	=>	$dropboxSecret
		);
		$tokenRequest = array(
			"method"	=>	"POST",
			"headers"	=>	$headers,
			"body"		=>	$body
		);
		$response = wp_remote_request($authURL, $tokenRequest);
		$body = json_decode($response["body"], true);
		$code = $response["response"]["code"];
		switch ($code) {
			case 400:
				$errorDescription = $body["error_description"];
				throw new Error($errorDescription);
				break;
			case 401:
				throw new Error(__("Bad or expired token, reauthorize with Dropbox now."));
				break;
			case 403:
				throw new Error(__("User is not allowed access."));
				break;
			case 409:
				throw new Error(__("Endpoint-specific error."));
				break;
			case 429:
				throw new Error(__("Too many requests... Please wait."));
				break;
			default:
				$modCode = $code % 500;
				if($modCode >= 0 && $modCode <= 99 && $modCode != $code) {
					// 5XX Error
					throw new Error("Dropbox: " . $body["user_message"]);
				}
				break;
		}
		$dropboxToken = $body["access_token"];
		$dropboxUserId = $body["account_id"];
		if ($dropboxToken 	== "" ||
				$dropboxToken 	== null ||
				$dropboxUserId 	== "" ||
				$dropboxUserId 	== null) {
			throw new Error(__("Response did not have token or the account id."));
		}
		?>
			<h3 name="dropbox_success"><i><b>Successfully got the token from Dropbox.</b></i></h3>
		<?php
		update_option("dropbox_token", $dropboxToken);
		update_option("dropbox_user_id", $dropboxUserId);
		return $dropboxToken;
	}

	/**
	 * Uploads a "$_FILES" file to dropbox.
	 * @since		1.0.0
	 * @return	boolean
	*/
	static function upload_file_to_dropbox($file) {
		$fileName = $file["name"];
		$tmpFile = $file["tmp_name"];
		$fileSize = $file["size"];
		$loadedFile = fopen($tmpFile, "r");
		$fileData = fread($loadedFile, $fileSize);
		fclose($loadedFile);
		$dropboxUploadURL = "https://content.dropboxapi.com/2/files/upload";
		$dropboxToken = DPIorDie_Admin::dropbox_token();
		$uploadToDropbox = DPIorDie_Admin::upload_to_dropbox();
		if($uploadToDropbox && $dropboxToken != null) {
			$dropboxFilePath = DPIorDie_Admin::dropbox_folder_path() . $fileName;
			$dropboxArgs = json_encode(array(
				"path"						=>	$dropboxFilePath,
				"mode"						=>	"add",
				"autorename"			=>	true,
				"mute"						=>	false,
				"strict_conflict"	=>	false
			));
			$auth = 'Bearer ' . $dropboxToken;
			$headers = array(
				'Authorization'		=>	$auth,
				'Accept'					=>	'application/json;ver=1.0',
				'Content-Type'		=>	'application/octet-stream',
				"Dropbox-API-Arg"	=>	$dropboxArgs
			);
			$uploadArgs = array(
				"method"	=>	"POST",
				"body"		=>	$fileData,
				"headers"	=>	$headers
			);
			$uploadResponse = wp_remote_request($dropboxUploadURL, $uploadArgs);
			$body = json_decode($uploadResponse["body"], true);
			if ($uploadResponse["response"]["code"] != 200) {
				throw new Error($body["error_description"]);
			}
			return true;
		} else if($uploadToDropbox) {
			throw new Error(__("No Dropbox API token in database."));
		}
	}

	/**
	 * Uploads to all services... for now one.
	 * @since		1.0.0
	 * @return	void
	*/
	static function upload_file($file) {
		DPIorDie_Admin::upload_file_to_dropbox($file);
		//TODO: Add more...
	}

	/**
	 * Prints the input to the max upload size.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_max_upload_size_field() {
		$maxUploadSize = DPIorDie_Admin::max_file_size();
		?>
			<input
				type="number"
				name="max_upload_size"
				id="max_upload_size"
				value="<?php echo esc_attr($maxUploadSize); ?>"
			/>
		<?php
	}

	/**
	 * Prints the input to the min dpi size.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_min_dpi_size_field() {
		$minDPISize = DPIorDie_Admin::min_dpi_size();
		?>
			<input
				type="number"
				name="min_dpi_size"
				id="min_dpi_size"
				value="<?php echo esc_attr($minDPISize); ?>"
			/>
		<?php
	}

	/**
	 * Prints the input to the max dpi size.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_max_dpi_size_field() {
		$maxDPISize = DPIorDie_Admin::max_dpi_size();
		?>
			<input
				type="number"
				name="max_dpi_size"
				id="max_dpi_size"
				value="<?php echo esc_attr($maxDPISize); ?>"
			/>
		<?php
	}

	/**
	 * Prints the input to the dropbox authorization code.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_dropbox_code_field() {
		$dropboxCode = DPIorDie_Admin::dropbox_code();
		?>
			<input
				type="text"
				name="dropbox_code"
				id="dropbox_code"
				value="<?php echo esc_attr($dropboxCode); ?>"
			/>
		<?php
	}

	/**
	 * Prints the input to the dropbox authorization token.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_dropbox_token_field() {
		$dropboxToken = DPIorDie_Admin::dropbox_token();
		?>
			<input
				type="text"
				name="dropbox_token"
				id="dropbox_token"
				value="<?php echo esc_attr($dropboxToken); ?>"
			/>
		<?php
	}

	/**
	 * Prints the input to the dropbox upload allowance.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_upload_to_dropbox_field() {
		$uploadToDropbox = DPIorDie_Admin::upload_to_dropbox();
		?>
			<input
				<?php echo ($uploadToDropbox ? "checked" : ""); ?>
				type="checkbox"
				name="upload_to_dropbox"
				id="upload_to_dropbox"
				value="upload_to_dropbox"
			/>
		<?php
	}

	/**
	 * Prints the input to the dropbox upload path.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_dropbox_folder_path_field() {
		$dropboxFolderPath = DPIorDie_Admin::dropbox_folder_path();
		?>
			<input
				type="text"
				name="dropbox_folder_path"
				id="dropbox_folder_path"
				value="<?php echo esc_attr($dropboxFolderPath); ?>"
			/>
		<?php
	}

	/**
	 * Return an instance of this class.
	 * @since		1.0.0
	 * @return	object	A single instance of this class.
	 */
	public static function get_instance() {

		if( ! is_super_admin() ) {
			return;
		}

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress
	 * Dashboard menu.
	 * @since	1.0.0
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_options_page(
			__( "DPIorDie Settings", $this->plugin_slug ),
			__( "DPIorDie", $this->plugin_slug ),
			"manage_options",
			$this->plugin_slug,
			array( $this, "display_plugin_admin_page" )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 * @since	1.0.0
	 */
	public function display_plugin_admin_page() {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized user');
		}

		if (isset($_POST["dpi_or_die_nonce"]) &&
				wp_verify_nonce($_POST['dpi_or_die_nonce'], 'dpi_or_die_set')) {

			if (isset($_POST['max_upload_size']) && $this::max_file_size() != $_POST['max_upload_size']) {
				update_option('max_upload_size', $_POST['max_upload_size'] * 1000000);
			}

			if (isset($_POST["max_dpi_size"]) && $this::max_dpi_size() != $_POST["max_dpi_size"]) {
				update_option("max_dpi_size", $_POST["max_dpi_size"]);
			}

			if (isset($_POST["dropbox_token"]) && $_POST["dropbox_token"] != $this::dropbox_token()) {
				update_option("dropbox_token", $_POST["dropbox_token"]);
			}

			if (isset($_POST["dropbox_code"]) && $_POST["dropbox_code"] != $this::dropbox_code()) {
				update_option("dropbox_code", $_POST["dropbox_code"]);
				$this::get_dropbox_token();
			}

			if (isset($_POST["upload_to_dropbox"]) && $this::upload_to_dropbox() != $_POST["upload_to_dropbox"]) {
				update_option("upload_to_dropbox", $_POST["upload_to_dropbox"]);
			}

			if (isset($_POST["dropbox_folder_path"]) && $this::dropbox_folder_path() != $_POST["dropbox_folder_path"]) {
				update_option("dropbox_folder_path", $_POST["dropbox_folder_path"]);
			}
		}

		include_once( "views/admin.php" );
	}

	/**
	 * Add settings action link to the plugins page.
	 * @since	1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				"settings" =>
					`<a href="` .
						admin_url(
							"options-general.php?page=" .
							$this->plugin_slug
						) .
					`">` .
						__("Settings", $this->plugin_slug) .
					"</a>"
			),
			$links
		);

	}

	/**
	 * Prints the preface to the settings page.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_settings_preface($args) {
		$si0 = __("Here you can change the filters and settings for image uploaders. You may also change the local upload size limit for all users on the site here. However, if the internal server limit is lower than your desired amount, the server in question will default to it's own internal limits instead.");
		$si1 = __("<strong>Note:</strong> 1,000,000 bytes or 1,000 kilobytes are equal to a singlular megabyte. Decimals are allowed and will be converted occordingly.");
		?>
			<p name="settings_info_0">
				<?php echo $si0; ?>
			</p>
			<p name="settings_info_1">
				<i>
					<?php echo $si1; ?>
				</i>
			</p>
		<?php
	}

	/**
	 * Prints the preface to the dropbox section.
	 * @since		1.0.0
	 * @return	void
	*/
	function render_dropbox_preface($args) {

		$authURL =
			"https://www.dropbox.com/oauth2/authorize?" .
			"client_id"			.	"=" .	urlencode($this::$dropboxClientId) . "&" .
			"response_type"	.	"="	.	"code";
		$dbi0 = __("Here you can set up the Dropbox integration. Files that make it past the DPI checks will be uploaded to the destination of your choosing on Dropbox. You just need to authorize the use. Click the link, authorize Dropbox, and copy the code it gives you once done. Once you have a token, you don't need to do anything else. The link <b>should</b> open in a new tab for you.");
		$dbi1 = __("We cannot at this time use Dropbox's \"token authentication\" flow, and have to use the \"code authentication\" flow. Where we get the token we need from the code you retreived from Dropbox. We cannot hardcode the redirect URL into Dropbox's settings because the redirect URL is different for each blog. We would have to make a website that manages your specific settings for your blog or a website that passes the token back to your blog with a redirect, and that doesn't make any sense, at this time.");
		$dbb0 = __("Authorize Dropbox");
		?>
			<p name="dropbox_info_0">
				<?php echo $dbi0; ?>
			</p>
			<p name="dropbox_info_1"><i>
				<?php echo $dbi1; ?>
			</i></p>
				<a
					href="<?php echo $authURL; ?>"
					target="_blank"
					rel="noopener noreferrer">
					<button type="button">
						<?php echo $dbb0; ?>
					</button>
				</a>
		<?php
	}

}
