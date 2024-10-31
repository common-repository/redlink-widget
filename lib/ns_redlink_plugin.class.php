<?php

class NS_REDLINK_Plugin {
	
	private $options;
	private static $instance;
	private static $redlinkapi;
	private static $name = 'NS_REDLINK_Plugin';
	private static $prefix = 'ns_redlink';
	private static $public_option = 'no';
	private static $textdomain = 'redlink-widget';
	
	private function __construct () {
		
		register_activation_hook(__FILE__, array(&$this, 'set_up_options'));
		
		/**
		 * Set up the settings.
		 */
		add_action('admin_init', array(&$this, 'register_settings'));
		
		/**
		 * Set up the administration page.
		 */		
		add_action('admin_menu', array(&$this, 'set_up_admin_page'));
		
		/**
		 * Fetch the options, and, if they haven't been set up yet, display a notice to the user.
		 */		 
		$this->get_options();
		
		if ('' == $this->options) {
			
			add_action('admin_notices', array(&$this, 'admin_notices'));
			
		}

		/**
		 * Add our widget when widgets get intialized.
		 */		
		add_action('widgets_init', create_function('', 'return register_widget("NS_Widget_Redlink");'));

		$this->load_text_domain();
		
	}
	
	public static function get_instance () {

		if (empty(self::$instance)) {
			
			self::$instance = new self::$name;
			
		}
		
		return self::$instance;

	}
	
	public function admin_notices () {
		
		echo '<div class="error fade">' . $this->get_admin_notices() . '</div>';
		
	}

	public function admin_page () {
		
		global $blog_id;	
		
		$api_user = (is_array($this->options)) ? $this->options['api-user'] : '';
		$api_pass = (is_array($this->options)) ? $this->options['api-pass'] : '';
		
		if (isset($_POST[self::$prefix . '_nonce'])) {
			
			$nonce = $_POST[self::$prefix . '_nonce'];
			
			$nonce_key = self::$prefix . '_update_options';
			
			if (! wp_verify_nonce($nonce, $nonce_key)) {
				
				echo '<div class="wrap">

					<div id="icon-options-general" class="icon32"><br /></div>

					<h2>Ustawienia widżetu REDlink</h2><p>' . __('Nawet tego nie próbuj...', 'redlink-widget') . '</p></div>';
				
				return false;
				
			} else {
				
				$new_api_user = $_POST[self::$prefix . '-api-user'];
				$new_api_pass = $_POST[self::$prefix . '-api-pass'];
				
				$new_options['api-user'] = $new_api_user;
				$new_options['api-pass'] = $new_api_pass;
				
				$this->update_options($new_options);
				
				$api_user = $this->options['api-user'];
				$api_pass = $this->options['api-pass'];
				
			}
			
		}
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32">
				<br />
			</div>
			<h2><?php echo __('Ustawienia widżetu REDlink', 'redlink-widget') ; ?></h2>
		<?php
		if (class_exists('SoapClient')) {
		?>
			<p><?php echo __('Wprowadź poprawny login i hasło do API REDlinka. Następnie skonfiguruj widżet z menu Wygląd >> Widżety.', 'redlink-widget') ?> 				
			</p>
				<form action="options.php" method="post">
			<?php settings_fields(self::$prefix . '_options'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo self::$prefix; ?>-api-user"><?php echo __('Login do API REDlinka', 'redlink-widget') ; ?></label>
					</th>
					<td>
						<input class="regular-text" id="<?php echo self::$prefix; ?>-api-user" name="<?php echo self::$prefix; ?>_options[api-user]" type="text" value="<?php echo $api_user; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo self::$prefix; ?>-api-pass"><?php echo __('Hasło do API REDlinka', 'redlink-widget') ; ?></label>
					</th>
					<td>
						<input class="regular-text" id="<?php echo self::$prefix; ?>-api-pass" name="<?php echo self::$prefix; ?>_options[api-pass]" type="password" value="<?php echo $api_pass; ?>" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php echo  __('Zapisz zmiany', 'redlink-widget'); ?>" />
			</p>
		</form>
		<?php	
		} else {
		?>	
		<p><?php echo __('Do używania tego widżetu potrzebna jest biblioteka PHP SOAP. Więcej informacji znajdziesz na stronie <a href="http://php.net/manual/en/book.soap.php">php.net</a>.');?></p><?php
			
		}
		?>
	</div>
	<?php
	}
	
	public function get_admin_notices ($type = 0) {
		
		global $blog_id;
		
		$notice = '<p>';
		
		if (1 == $type) {
			$notice .= __('Dane dostępowe do API są niepoprawne, spróbuj je ', 'redlink-widget') . ' <a href="' . get_admin_url($blog_id) . 'options-general.php?page=redlink-widget/lib/ns_redlink_plugin.class.php">' . __('poprawić', 'redlink-widget') . '</a>.';
		} else {
			$notice .= __('Zanim uruchomisz widżet REDlink, musisz go najpierw', 'redlink-widget') . ' <a href="' . get_admin_url($blog_id) . 'options-general.php?page=redlink-widget/lib/ns_redlink_plugin.class.php">' . __('skonfigurować', 'redlink-widget') . '</a>.';
		}
		
		$notice .= '</p>';
		
		return $notice;
		
	}
	
	public function get_redlinkapi () {
		
		$user = $this->get_api_user();
		$pass = $this->get_api_pass();
		
		if ((false == $user) || (false == $pass)) {
			
			return false;
			
		} else {
			
			if (empty(self::$redlinkapi)) {
			
				self::$redlinkapi = new RedlinkApi($user, $pass);
				
			}
			
			return self::$redlinkapi;
			
		}
		
	}
	
	public function get_options () {
		
		$this->options = get_option(self::$prefix . '_options');
		
		return $this->options;
		
	}
	
	public function load_text_domain () {
		
		load_plugin_textdomain(self::$textdomain, null, str_replace('lib', 'languages', dirname(plugin_basename(__FILE__))));
		
	}
	
	public function register_settings () {
		
		register_setting( self::$prefix . '_options', self::$prefix . '_options', array($this, 'validate_api_user'));
		register_setting( self::$prefix . '_options', self::$prefix . '_options', array($this, 'validate_api_pass'));
		
	}
	
	
	public function remove_options () {
		
		delete_option(self::$prefix . '_options');
		
	}
	
	public function set_up_admin_page () {
		
		add_submenu_page('options-general.php', 'Opcje widżetu REDlink', 'Widżet REDlink', 'activate_plugins', __FILE__, array(&$this, 'admin_page'));
		
	}

	public function set_up_options () {
		
		add_option(self::$prefix . '_options', '', '', self::$public_option);
		
	}
	
	public function validate_api_user ($api_user) {
		return $api_user;
	}
	
	public function validate_api_pass ($api_pass) {
		return $api_pass;
	}
	
	private function get_api_user() {
		
		if (is_array($this->options) && ! empty($this->options['api-user'])) {
		
			return $this->options['api-user'];
			
		} else {
			
			return false;
			
		}
		
	}
	
	private function get_api_pass() {
		
		if (is_array($this->options) && ! empty($this->options['api-pass'])) {
		
			return $this->options['api-pass'];
			
		} else {
			
			return false;
			
		}
		
	}
	
	private function update_options ($options_values) {
		
		$old_options_values = get_option(self::$prefix . '_options');
		
		$new_options_values = wp_parse_args($options_values, $old_options_values);
		
		update_option(self::$prefix .'_options', $new_options_values);
		
		$this->get_options();
		
	}
	
}
