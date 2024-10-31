<?php

class NS_Widget_Redlink extends WP_Widget {

	private $default_failure_message;
	private $default_loader_graphic = '/wp-content/plugins/redlink-widget/images/ajax-loader.gif';
	private $default_signup_text;
	private $default_success_message;
	private $default_title;
	private $successful_signup = false;
	private $subscribe_errors;

	private $ns_redlink_plugin;

	public function NS_Widget_Redlink () {

		$this->default_failure_message = __('Nie udało się zapisać nowego kontaktu');
		$this->default_signup_text = __('Zapisz się!');
		$this->default_success_message = __('Dziękujemy za dołączenie do naszego newslettera');
		$this->default_title = __('Dołącz do newslettera');

		$widget_options = array('classname' => 'widget_ns_redlink', 'description' => __( "Pokazuje formularz zapisu na listę mailingową REDlink.", 'redlink-widget'));

		$this->WP_Widget('ns_widget_redlink', __('Lista adresowa REDlink', 'redlink-widget'), $widget_options);

		$this->ns_redlink_plugin = NS_REDLINK_Plugin::get_instance();

		$this->default_loader_graphic = get_bloginfo('wpurl') . $this->default_loader_graphic;

		add_action('init', array(&$this, 'add_scripts'));

		add_action('parse_request', array(&$this, 'process_submission'));

	}

	public function add_scripts () {

		wp_enqueue_script('ns-redlink-widget', get_bloginfo('wpurl') . '/wp-content/plugins/redlink-widget/js/redlink-widget.js', array('jquery'), false);

	}

	public function form ($instance) {

		$redlinkapi = $this->ns_redlink_plugin->get_redlinkapi();

		if (false === $redlinkapi) {

			$form = $this->ns_redlink_plugin->get_admin_notices();

		} else if (false === ($groups = $redlinkapi->getGroups())) {

			$form = $this->ns_redlink_plugin->get_admin_notices(1);

		} else {

			$this->lists = $groups;

			$defaults = array(

				'failure_message' => $this->default_failure_message,
				'title' => $this->default_title,
				'signup_text' => $this->default_signup_text,
				'success_message' => $this->default_success_message,
				'collect_first' => false,
				'collect_last' => false,
				'collect_mobile' => false,
				'collect_compay' => false,

			);

			$vars = wp_parse_args($instance, $defaults);

			extract($vars);

			$form = '<h3>' . __('Ustawienia ogólne', 'redlink-widget') . '</h3><p><label>' . __('Tytuł:', 'redlink-widget') . '<input class="widefat" id=""' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';

			$form .= '<p><label>' . __('Grupa kontaktów:', 'redlink-widget') . '<br />';

			$form .= '<select class="widefat" id="' . $this->get_field_id('current_mailing_list') . '" name="' . $this->get_field_name('current_mailing_list') . '">';

			$selected = (isset($current_mailing_list) && $current_mailing_list == '') ? ' selected="selected" ' : '';

			$form .= '<option ' . $selected . 'value="">' . __('Bez grupy', 'redlink-widget') . '</option>';

			$this->lists = (array) $this->lists;

			foreach ($this->lists as $key => $list) {

				$list_id   = isset($list->GroupId) ? $list->GroupId : '';
				$list_name = isset($list->GroupName) ? $list->GroupName : '';

				if (strtoupper($list_id) == 'ALLCONTACTS') continue;

				$selected = (isset($current_mailing_list) && $current_mailing_list == $list_id) ? ' selected="selected" ' : '';

				$form .= '<option ' . $selected . 'value="' . $list_id . '">' . __($list_name, 'redlink-widget') . '</option>';
			}

			$form .= '</select></label></p><p>' . __('(wskaż nazwę grupy do jakiej mają trafiać nowe kontakty)', 'redlink-widget') . '</p>';

			$form .= '<p><label>' . __('Tekst przycisku:', 'redlink-widget') . '<input class="widefat" id="' . $this->get_field_id('signup_text') .'" name="' . $this->get_field_name('signup_text') . '" value="' . $signup_text . '" /></label></p>';

			$form .= '<h3>' . __('Dane personalne', 'redlink-widget') . '</h3><p>' . __("Wskaż opcjonalne pola do wypełnienia przez użytkownika:", 'redlink-widget') . '</p><p><input type="checkbox" class="checkbox" id="' . $this->get_field_id('collect_first') . '" name="' . $this->get_field_name('collect_first') . '" ' . checked($collect_first, true, false) . ' /> <label for="' . $this->get_field_id('collect_first') . '" >' . __('imię', 'redlink-widget') . '</label><br /><input type="checkbox" class="checkbox" id="' . $this->get_field_id('collect_last') . '" name="' . $this->get_field_name('collect_last') . '" ' . checked($collect_last, true, false) . ' /> <label>' . __('nazwisko', 'redlink-widget') . '</label><br /><input type="checkbox" class="checkbox" id="' . $this->get_field_id('collect_mobile') . '" name="' . $this->get_field_name('collect_mobile') . '" ' . checked($collect_mobile, true, false) . ' /> <label>' . __('telefon komórkowy', 'redlink-widget') . '</label><br /><input type="checkbox" class="checkbox" id="' . $this->get_field_id('collect_company') . '" name="' . $this->get_field_name('collect_company') . '" ' . checked($collect_company, true, false) . ' /> <label>' . __('nazwa firmy', 'redlink-widget') . '</label></p>';

			$form .= '<h3>' . __('Komunikaty', 'redlink-widget') . '</h3><p>' . __('Wprowadź treść informacji do pokazania użytkownikowi po wysłaniu formularza.', 'redlink-widget') . '</p><p><label>' . __('Udany zapis:', 'redlink-widget') . '<textarea class="widefat" id="' . $this->get_field_id('success_message') . '" name="' . $this->get_field_name('success_message') . '">' . $success_message . '</textarea></label></p><p><label>' . __('Wystąpił błąd:', 'redlink-widget') . '<textarea class="widefat" id="' . $this->get_field_id('failure_message') . '" name="' . $this->get_field_name('failure_message') . '">' . $failure_message . '</textarea></label></p>';

		}

		echo $form;

	}

	public function process_submission () {

		// AJAX call
		if (isset($_GET[$this->id_base . '_email'])) {

			header("Content-Type: application/json");

			//Assume the worst.
			$response = '';
			$result = array('success' => false, 'error' => $this->get_failure_message($_GET['ns_redlink_number']));

			$merge_vars = array();

			if (! is_email($_GET[$this->id_base . '_email'])) { //Use WordPress's built-in is_email function to validate input.

				$result['error'] .= ' (' . __('niepoprawny adres e-mail', 'redlink-widget') . ')';
				$response = json_encode($result); //If it's not a valid email address, just encode the defaults.

			} else {

				$redlinkapi = $this->ns_redlink_plugin->get_redlinkapi();

				if (false == $redlinkapi) {

					$response = json_encode($result);

				} else {

					if (!empty($_GET[$this->id_base . '_first_name'])) {

						$merge_vars['FNAME'] = $_GET[$this->id_base . '_first_name'];

					}

					if (!empty($_GET[$this->id_base . '_last_name'])) {

						$merge_vars['LNAME'] = $_GET[$this->id_base . '_last_name'];

					}

					if (!empty($_GET[$this->id_base . '_mobile'])) {

						$merge_vars['MOBILE'] = $_GET[$this->id_base . '_mobile'];

					}

					if (!empty($_GET[$this->id_base . '_company'])) {

						$merge_vars['COMPANY'] = $_GET[$this->id_base . '_company'];

					}

					$contact['Email'] = $_GET[$this->id_base . '_email'];

					if (isset($merge_vars['FNAME'])) {
						$contact['FirstName'] = $merge_vars['FNAME'];
					}

					if (isset($merge_vars['LNAME'])) {
						$contact['LastName']    = $merge_vars['LNAME'];
					}

					if (isset($merge_vars['MOBILE'])) {
						$contact['MobilePhone'] = $merge_vars['MOBILE'];
					}

					if (isset($merge_vars['COMPANY'])) {
						$contact['CompanyName'] = $merge_vars['COMPANY'];
					}

					$subscribed = $redlinkapi->addContact($contact);

					if (false === $subscribed) {

						$result['error'] .= ' (' . $redlinkapi->getError() . ')';
						$response = json_encode($result);

					} else {

						$grouped = true;

						if (strlen($this->get_current_mailing_list_id($_GET['ns_redlink_number']))) {
							$grouped = $redlinkapi->addContactsToGroup(array($subscribed), $this->get_current_mailing_list_id($_GET['ns_redlink_number']));
						}

						if (false === $grouped) {

							$result['error'] .= ' (' . $redlinkapi->getError() . ')';
							$response = json_encode($result);

						} else {

							$result['success'] = true;
							$result['error'] = '';
							$result['success_message'] =  $this->get_success_message($_GET['ns_redlink_number']);
							$response = json_encode($result);

						}

					}

				}

			}

			exit($response);

		// No AJAX call
		} elseif (isset($_POST[$this->id_base . '_email'])) {

			$this->subscribe_errors = '<div class="error">'  . $this->get_failure_message($_POST['ns_redlink_number']) .  '</div>';

			if (! is_email($_POST[$this->id_base . '_email'])) {

				return false;

			}

			$redlinkapi = $this->ns_redlink_plugin->get_redlinkapi();

			if (false == $redlinkapi) {

				return false;

			}

			if (!empty($_POST[$this->id_base . '_first_name'])) {

				$merge_vars['FNAME'] = $_POST[$this->id_base . '_first_name'];

			}

			if (!empty($_POST[$this->id_base . '_last_name'])) {

				$merge_vars['LNAME'] = $_POST[$this->id_base . '_last_name'];

			}

			if (!empty($_POST[$this->id_base . '_mobile'])) {

				$merge_vars['MOBILE'] = $_POST[$this->id_base . '_mobile'];

			}

			if (!empty($_POST[$this->id_base . '_company'])) {

				$merge_vars['COMPANY'] = $_POST[$this->id_base . '_company'];

			}

			$contact['Email']       = $_POST[$this->id_base . '_email'];

			if (isset($merge_vars['FNAME'])) {
				$contact['FirstName'] = $merge_vars['FNAME'];
			}

			if (isset($merge_vars['LNAME'])) {
				$contact['LastName']    = $merge_vars['LNAME'];
			}

			if (isset($merge_vars['MOBILE'])) {
				$contact['MobilePhone'] = $merge_vars['MOBILE'];
			}

			if (isset($merge_vars['COMPANY'])) {
				$contact['CompanyName'] = $merge_vars['COMPANY'];
			}

			$subscribed = $redlinkapi->addContact($contact);

			if (false === $subscribed) {

				return false;

			} else {


				$grouped = true;

				if (strlen($this->get_current_mailing_list_id($_POST['ns_redlink_number']))) {
					$grouped = $redlinkapi->addContactsToGroup(array($subscribed), $this->get_current_mailing_list_id($_POST['ns_redlink_number']));
				}

				if (false === $grouped) {

					return false;

				} else {

					$this->subscribe_errors = '';

					setcookie($this->id_base . '-' . $this->number, $this->hash_mailing_list_id($this->number), time() + 31556926);

					$this->successful_signup = true;

					$this->signup_success_message = '<p>' . $this->get_success_message($_POST['ns_redlink_number']) . '</p>';

					return true;

				}

			}

		}

	}

	public function update ($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['collect_first'] = ! empty($new_instance['collect_first']);

		$instance['collect_last'] = ! empty($new_instance['collect_last']);

		$instance['collect_mobile'] = ! empty($new_instance['collect_mobile']);

		$instance['collect_company'] = ! empty($new_instance['collect_company']);

		$instance['current_mailing_list'] = esc_attr($new_instance['current_mailing_list']);

		$instance['failure_message'] = esc_attr($new_instance['failure_message']);

		$instance['signup_text'] = esc_attr($new_instance['signup_text']);

		$instance['success_message'] = esc_attr($new_instance['success_message']);

		$instance['title'] = esc_attr($new_instance['title']);

		return $instance;

	}

	public function widget ($args, $instance) {

		extract($args);

		if ((isset($_COOKIE[$this->id_base . '-' . $this->number]) && $this->hash_mailing_list_id($this->number) == $_COOKIE[$this->id_base . '-' . $this->number]) || false == $this->ns_redlink_plugin->get_redlinkapi()) {

			return 0;

		} else {

			$widget = $before_widget . $before_title . $instance['title'] . $after_title;

			if ($this->successful_signup) {

				$widget .= $this->signup_success_message;

			} else {

				$collect_first = '';

				if ($instance['collect_first']) {

					$collect_first = '<label>' . __('Imię:', 'redlink-widget') . '<input type="text" style="float:right;margin-bottom:3px;" name="' . $this->id_base . '_first_name" /></label><br style="clear:both;" />';

				}

				$collect_last = '';

				if ($instance['collect_last']) {

					$collect_last = '<label>' . __('Nazwisko:', 'redlink-widget') . '<input type="text" style="float:right;margin-bottom:3px;" name="' . $this->id_base . '_last_name" /></label><br style="clear:both;" />';

				}

				$collect_mobile = '';

				if ($instance['collect_mobile']) {

					$collect_mobile = '<label>' . __('Komórka:', 'redlink-widget') . '<input type="text" style="float:right;margin-bottom:3px;" name="' . $this->id_base . '_mobile" /></label><br style="clear:both;" />';

				}

				$collect_company = '';

				if ($instance['collect_company']) {

					$collect_company = '<label>' . __('Firma:', 'redlink-widget') . '<input type="text" style="float:right;margin-bottom:3px;" name="' . $this->id_base . '_company" /></label><br style="clear:both;" />';

				}

				$widget .= '<form action="' . $_SERVER['REQUEST_URI'] . '" id="' . $this->id_base . '_form-' . $this->number . '" method="post">' . $this->subscribe_errors . $collect_first . $collect_last . $collect_mobile . $collect_company . '<label>' . __('E-mail: *', 'redlink-widget') . '</label><input type="hidden" name="ns_redlink_number" value="' . $this->number . '" /><input type="text" style="float:right;margin-bottom:3px;" name="' . $this->id_base . '_email" /><br style="clear:both;" /><input class="button" style="float:right;" type="submit" name="' . __($instance['signup_text'], 'redlink-widget') . '" value="' . __($instance['signup_text'], 'redlink-widget') . '" /><br style="clear:both;" /></form><script type="text/javascript"> jQuery(\'#' . $this->id_base . '_form-' . $this->number . '\').ns_redlink_widget({"url" : "' . $_SERVER['PHP_SELF'] . '", "cookie_id" : "'. $this->id_base . '-' . $this->number . '", "cookie_value" : "' . $this->hash_mailing_list_id($this->number) . '", "loader_graphic" : "' . $this->default_loader_graphic . '"}); </script>';

			}

			$widget .= $after_widget;

			echo $widget;

		}

	}

	private function hash_mailing_list_id ($number = null) {

		$options = get_option($this->option_name);

		$hash = md5($options[$number]['current_mailing_list']);

		return $hash;

	}

	private function get_current_mailing_list_id ($number = null) {

		$options = get_option($this->option_name);

		return $options[$number]['current_mailing_list'];

	}

	private function get_failure_message ($number = null) {

		$options = get_option($this->option_name);

		return $options[$number]['failure_message'];

	}

	private function get_success_message ($number = null) {

		$options = get_option($this->option_name);

		return $options[$number]['success_message'];

	}

}
