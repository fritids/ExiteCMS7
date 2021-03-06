<?php
/*---------------------------------------------------------------------+
| ExiteCMS Content Management System                                   |
+----------------------------------------------------------------------+
| Copyright 2006-2008 Exite BV, The Netherlands                        |
| for support, please visit http://www.exitecms.org                    |
+----------------------------------------------------------------------+
| Some code derived from PHP-Fusion, copyright 2002 - 2006 Nick Jones  |
+----------------------------------------------------------------------+
| Released under the terms & conditions of v2 of the GNU General Public|
| License. For details refer to the included gpl.txt file or visit     |
| http://gnu.org                                                       |
+----------------------------------------------------------------------+
| $Id::                                                               $|
+----------------------------------------------------------------------+
| Last modified by $Author::                                          $|
| Revision number $Rev::                                              $|
+---------------------------------------------------------------------*/
if (eregi("theme_functions.php", $_SERVER['PHP_SELF']) || !defined('INIT_CMS_OK')) die();

// load the Smarty template engine
require_once PATH_INCLUDES."Smarty-2.6.22/Smarty.class.php";

// extend Smarty with the ExiteCMS custom bits
class ExiteCMS_Smarty extends Smarty {

    /**#@+
     * ExiteCMS Smarty Configuration Section
     */

    /**
     * Array with names of directories where templates can be located.
     *
     * @var string
     */
	var $template_dir = array('templates');

    /**#@-*/
    /**
     * The class constructor.
     */
	function ExiteCMS_Smarty() {
		global $settings, $userdata;

		$this->Smarty();

		// debugging needed?
		$this->debugging = false;

		// on-the-fly compilation needed?
		$this->compile_check = true;

		// set the compile ID for this website/theme (themes can have different templates!)
		if (isset($userdata['user_theme']) && $userdata['user_theme'] != "Default") {
			$this->compile_id = $_SERVER['SERVER_NAME']."~".$userdata['user_theme'];
		} else {
			$this->compile_id = $_SERVER['SERVER_NAME']."~".$settings['theme'];
		}

		// caching required?
		$this->caching = 0;

		// path definitions
		$this->config_dir = PATH_THEME.'templates/configs';
		$this->compile_dir = PATH_ROOT.'files/tplcache';
		$this->cache_dir = PATH_ROOT.'files/cache';

		// PHP in Templates? Don't think so!
		$this->php_handling = SMARTY_PHP_REMOVE;

		// Template security settings: allow PHP functions
		$this->security = false;
	}

    /**
     * get a concrete filename for automagically created content
     *
     * @param string $auto_base
     * @param string $auto_source
     * @param string $auto_id
     * @return string
     * @staticvar string|null
     * @staticvar string|null
     */
    function _get_auto_filename($auto_base, $auto_source = null, $auto_id = null)
    {
        $_compile_dir_sep =  $this->use_sub_dirs ? DIRECTORY_SEPARATOR : '^';
        $_return = $auto_base . DIRECTORY_SEPARATOR;

        if(isset($auto_source)) {
            // make source name safe for filename
            $auto_source = urlencode(basename($auto_source));
			if (preg_match("%[\\\/:;*?\"\[\]\%]%", $auto_source)) {
				$auto_source = md5($auto_source);
			}
			$_return .= $auto_source."~";
        }

        if(isset($auto_id)) {
            // make auto_id safe for directory names
            $auto_id = str_replace('%7C',$_compile_dir_sep,(urlencode($auto_id)));
            $auto_id = str_replace('%7E','~',$auto_id);
			$_return .= $auto_id;
        }

        return $_return;
    }

    /**
     * Returns the last modified timestamp of a template, or false if not found.
     *
     * @param string $tpl_file
     * @return mixed
     */
    function template_timestamp($tpl_file)
    {
        $_params = array('resource_name' => $tpl_file, 'quiet'=>true, 'get_source'=>false);
        if ($this->_fetch_resource_info($_params)) {
			return $_params['resource_timestamp'];
		} else {
			return false;
		}
    }
}

// Smarty template engine definitions and initialisation

// initialize the template engine
$template = & new ExiteCMS_Smarty();

// plugin's, where to find them?
$plugins_dir = array();
// first check if there's one defined in the current theme
if (is_dir(PATH_THEME."template/plugins")) $plugins_dir[] = PATH_THEME."template/plugins";
// next, check the ExiteCMS custom plugins
$plugins_dir[] = PATH_INCLUDES.'template-plugins';
// and finaly, use the default Smarty plugins
$plugins_dir[] = 'smarty-plugins';

$template->plugins_dir = $plugins_dir;

// default, only check the CMS template directory for templates
// custom dirs will be added at runtime, based on the template type
$template->template_dir = array(PATH_INCLUDES.'templates');;

// Register the panel template resource
$template->register_resource('panel', array('resource_panel_source', 'resource_panel_timestamp', 'resource_panel_secure', 'resource_panel_trusted'));

// Register the string template resource
$template->register_resource('string', array('resource_string_source', 'resource_string_timestamp', 'resource_string_secure', 'resource_string_trusted'));

// Array to store panels
$template_panels = array();

// Array to store panel variables
$template_variables = array();

/*-----------------------------------------------------+
| load_templates - process templates                   |
+-----------------------------------------------------*/
function load_templates($_column='', $_name='', $_output='html') {
	global $settings, $locale, $userdata, $db_prefix, $aidlink,
			$template, $template_panels, $template_variables,
			$_loadstats, $_headparms, $_bodyparms, $_last_updated;


	// make sure the output type is valid, default to html
	if ($_output != 'html' && $_output != 'json' && $_output != 'var') $_output = 'html';

	// store the current locales. We need to restore them later
	$current_locale = $locale;

	// reset all assigned template variables
	$template->clear_all_assign();

	// Initialise the $locale array
	$locale = array();

	// Load the global language file
	locale_load("main.global");

	// assign CMS website settings to the template
	$template->assign("settings", $settings);

	// assign the current users record to the template
	$template->assign("userdata", $userdata);

	// find the requested template and variable definitions
	foreach($template_panels as $panel_name => $panel) {
		// are we interested in this panel?
		if (($_column == "" || $panel['type'] == $_column) && ($_name == "" || $panel['name'] == $_name)) {
			// panel preprocessing, if defined
			$no_panel_displayed = false;
			$template->assign('_style', '');
			if (isset($panel['panel_type'])) {
				switch($panel['panel_type']) {
					case "file":
						if (file_exists($panel['panel_code']))
							include $panel['panel_code'];
						else
							$no_panel_displayed = true;
						break;
					case "dynamic":
						$variables = array();
						eval(stripslashes($panel['panel_code']));
						// define the dynamic panel
						$panel['name'] = 'dynamic_panel.'.$panel['id'];
						$panel['template'] = 'panel:'.$panel['id'];
						$template_variables[$panel['name']] = $variables;
						break;
					default:
						break;
				}
			}
			if ($no_panel_displayed) continue;

			// assign the panel variables
			$panel_name = isset($panel['name']) ? $panel['name'] : "";
			if (isset($template_variables[$panel_name]) && is_array($template_variables[$panel_name])) {
				foreach($template_variables[$panel_name] as $varname => $var) {
					$template->assign($varname, $var);
				}
			}
			// need the panel definition to be available too...
			$template->assign('_name', isset($panel['name'])?$panel['name']:"");
			$template->assign('_title', isset($panel['title'])?$panel['title']:"");
			$template->assign('_state', isset($panel['state'])?$panel['state']:"");
			$template->assign('_type', isset($panel['panel_type'])?$panel['panel_type']:"");

			// if one or more locales are assigned to this panel, load them first
			if (isset($panel['locale'])) {
				if (is_array($panel['locale'])) {
					foreach($panel['locale'] as $panel_locale) {
						locale_load($panel_locale);
					}
				} else {
					locale_load($panel['locale']);
				}
			}
			// then assign the locales to the template
			$template->assign("locale", $locale);

			// assign CMS admin security aidlink
			$template->assign("aidlink", $aidlink);

			// if defined, add header parameters
			if ($_column == 'header') {
				if (isset($_headparms)) $template->assign("headparms", $_headparms);
				if (isset($_bodyparms)) $template->assign("bodyparms", $_bodyparms);
			}

			// update the loadtime counter
			if ($_column == 'footer') {
				$_loadtime = explode(" ", microtime());
				$_loadstats['time'] += $_loadtime[1] + $_loadtime[0];
				// and assign it for use in the template
				$template->assign("_loadstats", $_loadstats);
			}

			// process the output type
			switch ($_output) {

				case "var":

					// variable to store template output in
					if (!isset($retval)) $retval = "";

					// ** missing break on purpose! **

				case "html":

					// store the current template directories, we need to restore them later
					$td = $template->template_dir;

					//if this is a module template...
					$tpl_parts = explode(".", $panel['template']);
					if ($tpl_parts[0] == "modules") {
						$template->template_dir = array_merge(array(PATH_MODULES.$tpl_parts[1].'/templates'), $template->template_dir);
					}

					//if this is a tools template...
					$tpl_parts = explode(".", $panel['template']);
					if ($tpl_parts[0] == "admin" && $tpl_parts[1] == "tools") {
						$template->template_dir = array_merge(array(PATH_ADMIN.'tools/templates'), $template->template_dir);
					}

					// whatever template, look in the theme template directory first!
					if (is_dir(PATH_THEME."templates/templates")) {
						$template->template_dir = array_merge(array(PATH_THEME."templates/templates"), $template->template_dir);
					}

					// if a template is defined, get the last modified date, and load the template
					if (isset($panel['template'])) {
						// get the timestamp of the template, and update the last update timestamp if newer
						$ts = $template->template_timestamp($panel['template']);
						$_last_updated = isset($_last_updated) ? ($ts > $_last_updated ? $ts : $_last_updated ) : $ts;
						$template->assign("_last_updated", $_last_updated);
						// and load the template
						if ($_output == "html") {
							$template->display($panel['template']);
						} else {
							$retval .= $template->fetch($panel['template']);
						}
					}

					// restore the template direcory
					$template->template_dir = $td;

					break;

				case "json":

					// get all assigned template variables
					$vars = $template->get_template_vars();

					$retval = array2json($vars);

					break;

				default:
					terminate("invalid output type: $_output");
					break;

			}
		}
	}

	// restore the current locales.
	$locale = $current_locale;

	// send the return value back if needed
	if (isset($retval)) return $retval;
}

/*-----------------------------------------------------+
| load_panels - load the template array with panels    |
+-----------------------------------------------------*/
function load_panels($column) {
	global $db_prefix, $locale, $settings, $userdata, $template, $template_panels;

	$opening_page = substr($settings['opening_page'],0,1) != "/" ? (BASEDIR.$settings['opening_page']) : $settings['opening_page'];

	// parameter validation and processing
	$column = strtolower(trim($column));
	switch ($column) {
		case "":
			// no where clause, return all panels
			break;
		case "header":
			// get the header panels
			$where = "panel_side='0'";
			break;
		case "left":
			// get the left-side panels
			$where = "panel_side='1'";
			break;
		case "upper":
			// get the upper-center panels
			$where = "panel_side='2'";
			if (FUSION_URL != BASEDIR."index.php" && strpos($opening_page, FUSION_URL) !== 0) {
				$where .= " AND panel_display='1'";
			}
			break;
		case "lower":
			// get the lower-center panels
			$where = "panel_side='3'";
			if (FUSION_URL != BASEDIR."index.php" && strpos($opening_page, FUSION_URL) !== 0) {
				$where .= " AND panel_display='1'";
			}
			break;
		case "right":
			// get the right-side panels
			$where = "panel_side='4'";
			break;
		case "top":
			// get the top panels
			$where = "panel_side='5'";
			break;
		case "footer":
			// get the footer panels
			$where = "panel_side='6'";
			break;
		default:
			// invalid parameter. Generate a notice
			trigger_error("theme_functions: getpanels(): invalid 'column' parameter passed", E_USER_NOTICE);
			return false;
	}
	//
	switch ($settings['panels_localisation']) {
		case "multiple":
			$where .= ($where == "" ? "" : " AND ")."panel_locale = '".$settings['locale_code']."'";
			break;
		default:
	}

	$p_res = dbquery("SELECT * FROM ".$db_prefix."panels WHERE ".$where." AND panel_status='1' ORDER BY panel_order");
	if (dbrows($p_res) != 0) {
		// loop through the panels found
		while ($p_data = dbarray($p_res)) {
			// we only need panels the user has access to
			if (checkgroup($p_data['panel_access'])) {
				// initialize the panel array
				$_panel = array();
				$_panel['name'] = "";
				$_panel['id'] = $p_data['panel_id'];
				$_panel['type'] = $column;
				$_panel['title'] = $p_data['panel_name'];
				$_panel['panel_type'] = $p_data['panel_type'];
				switch($p_data['panel_type']) {
					case "file":
						// check for module directory for backward compatibility
						if (@is_dir(PATH_MODULES.$p_data['panel_filename'])) {
							$_panel['name'] = 'modules.'.$p_data['panel_filename'];
							$_panel['template'] = 'modules.'.$p_data['panel_filename'].".tpl";
							$_panel['panel_code'] = PATH_MODULES.$p_data['panel_filename']."/".$p_data['panel_filename'].".php";
						} else {
							$_panel['template'] = 'modules.'.substr(basename($p_data['panel_filename']),0,-4).".tpl";
							$_panel['name'] = 'modules.'.substr(basename($p_data['panel_filename']),0,-4);
							$_panel['panel_code'] = PATH_MODULES.$p_data['panel_filename'];
						}
						// check if there is a locale for this panel
						$_panel['locale'] = 'modules.'.dirname($p_data['panel_filename']);
						$result = dbquery("SELECT * FROM ".$db_prefix."locales WHERE locales_name = '".$_panel['locale']."' LIMIT 1");
						if (dbrows($result)==0) unset($_panel['locale']);
						break;
					case "dynamic":
						$_panel['name'] = 'dynamic_panel_'.$_panel['id'];
						$_panel['panel_code'] = $p_data['panel_code'];
						break;
				}
				$_panel['state'] = $p_data['panel_state'];
				// check if there's a panel state stored. If so, restore the previous panel state
				$session_var = "box_".str_replace(".", "_", $_panel['name']);
				if (isset($userdata['user_datastore']['panelstates'][$session_var])) {
					$_panel['state'] = $userdata['user_datastore']['panelstates'][$session_var];
				}
				// if this panel is not defined as hidden, add it to the template array
				if ($_panel['state'] < 2) {
					$template_panels[] = $_panel;
				}
			}
		}
	}
	return true;
}

/*-----------------------------------------------------+
| count_panels - count the panels of a given type      |
+-----------------------------------------------------*/
function count_panels($column) {
	global $template_panels;

	if (!is_array($template_panels)) return false;

	$count = 0;

	// parameter validation and processing
	$column = strtolower(trim($column));
	switch ($column) {
		case "left":
		case "right":
		case "upper":
		case "lower":
		case "top":
		case "header":
		case "footer":
			foreach($template_panels as $panel_name => $panel) {
				if($panel['type'] == $column) $count++;
			}
			break;
		case "body":
			foreach($template_panels as $panel_name => $panel) {
				if(!in_array($panel['type'], array('left', 'right', 'top', 'upper', 'lower', 'header', 'footer'))) $count++;
			}
			break;
		case "all":
			$count = count($template_panels);
		default:
			return false;
	}
	return $count;
}

/*-----------------------------------------------------+
| theme initialisation function,called by theme.php    |
+-----------------------------------------------------*/
function theme_init() {

	// make sure these constants exists
	if (!defined('LOAD_TINYMCE')) define('LOAD_TINYMCE', false);
	if (!defined('LOAD_HOTEDITOR')) define('LOAD_HOTEDITOR', false);
}

/*-----------------------------------------------------+
| theme cleanup function, to be called by theme.php    |
+-----------------------------------------------------*/
function theme_cleanup() {

	global $db_prefix, $userdata, $_db_log, $_db_logs, $template, $settings;

	// update the user's datastore
	if (iMEMBER	&& isset($userdata['user_datastore'])) {
		$result = dbquery("UPDATE ".$db_prefix."users SET user_datastore = '".mysql_real_escape_string(serialize($userdata['user_datastore']))."' WHERE user_id = '".$userdata['user_id']."'");
	}

	// flush any session info
	session_clean_close();

	// clean-up tasks, will be executed by all super-admins
	// WANWIZARD - 20070716 - THIS NEEDS TO BE MOVED TO A CRON JOB !!!
	$_db_logs[] = array("--- clean up code --- not included in the footer information --- needs to be moved to a cron process", 0);
	if ($userdata['user_level'] >= 103) {
		$minute = 60; $hour = $minute * 60; $day = $hour * 24;
		// flood control: set to 5 minutes
		$result = dbquery("DELETE LOW_PRIORITY FROM ".$db_prefix."flood_control WHERE flood_timestamp < '".(time() - $minute * 5)."'");
		// thread notifies: set to 90 days
		$result = dbquery("DELETE LOW_PRIORITY FROM ".$db_prefix."thread_notify WHERE notify_datestamp < '".(time() - $day * 90)."'");
		// new registered users: set to 7 days
		$result = dbquery("DELETE LOW_PRIORITY FROM ".$db_prefix."new_users WHERE user_datestamp < '".(time() - $day * 7)."'");
		// deactivate accounts with a bad email address after 90 days (available since v7.0 rev.1060)
		if ($settings['revision'] >= 1060) {
			$result = dbquery("UPDATE ".$db_prefix."users SET user_status = 1, user_ban_reason = '', user_ban_expire = '".time()."' WHERE user_bad_email > 0 AND user_bad_email < '".(time() - $day * 90)."'");
		}
		// read threads indicators: use the defined threshold (available since v7.0 rev.1193)
		if ($settings['revision'] >= 1193) {
			$result = dbquery("DELETE LOW_PRIORITY FROM ".$db_prefix."threads_read WHERE thread_last_read < '".$settings['unread_threshold']."'", false);
		}
	}

	// check if we have had query debugging active. If so, display the result just before the footer panel(s)
	if ($_db_log && is_array($_db_logs) && count($_db_logs)) {
		// check if we want optimizer output as well
		if (isset($settings['debug_sql_explain']) && $settings['debug_sql_explain']) {
			// don't want the explain in the logs as well
			$_db_log = false;
			// get all SELECT's, and perform an EXPLAIN to see if they can be optimized
			foreach($_db_logs as $key => $value) {
				if (substr($value[0],0,7) == "SELECT ") {
					$result = dbquery("EXPLAIN ".$value[0]);
					while ($data = dbarray($result)) {
						if (!isset($_db_logs[$key]['explain'])) $_db_logs[$key]['explain'] = array();
						$_db_logs[$key]['explain'][] = $data;
					}
				}
			}
		}
		$template->assign('queries', $_db_logs);
		$template->template_dir = array(PATH_INCLUDES.'templates', PATH_THEMES.$settings['theme'].'/templates/templates');
		$template->display('_query_debug.tpl');
	}

	// close the database connection
	mysql_close();

	echo "</body>\n</html>\n";

	// store the current URL in a cookie. We might need to redirect to it later
	setcookie('last_url', FUSION_REQUEST, 0, '/');

	// and flush any output remaining
	ob_end_flush();
}

/*-----------------------------------------------------+
| resource_panel - Smarty panel resource callbacks     |
+-----------------------------------------------------*/
function resource_panel_source($tpl_name, &$tpl_source, &$smarty) {

	global $db_prefix;

	// get the panel record
	$result = dbquery("SELECT * FROM ".$db_prefix."panels WHERE panel_id = '$tpl_name'");

	if ($data = dbarray($result)) {
		// if the record exists, return it in the $tpl_source variable
        $tpl_source = stripslashes($data['panel_template']);
        return true;
	} else {
		// panel record not found
		return false;
	}
}

function resource_panel_timestamp($tpl_name, &$tpl_timestamp, &$smarty) {

	global $db_prefix;

	// get the panel record
	$result = dbquery("SELECT * FROM ".$db_prefix."panels WHERE panel_id = '$tpl_name'");

	if ($data = dbarray($result)) {
		// if the record exists, return the timestamp in the $tpl_timestamp variable
		$tpl_timestamp = $data['panel_datestamp'];
		return true;
	} else {
		// panel record not found
		return false;
	}
}

function resource_panel_secure($tpl_name, &$smarty) {

    // assume all templates are secure
	return true;
}

function resource_panel_trusted($tpl_name, &$smarty) {

    // not used for templates
}

/*-----------------------------------------------------+
| resource_string - Smarty string resource callbacks   |
+-----------------------------------------------------*/
function resource_string_source($tpl_name, &$tpl_source, &$smarty) {

	$tpl_source = $tpl_name;
	return true;
}

function resource_string_timestamp($tpl_name, &$tpl_timestamp, &$smarty) {

	$tpl_timestamp = time();
	return true;
}

function resource_string_secure($tpl_name, &$smarty) {

    // assume all templates are secure
	return true;
}

function resource_string_trusted($tpl_name, &$smarty) {

    // not used for templates
}
?>
