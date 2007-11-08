<?php
/*---------------------------------------------------+
| ExiteCMS Content Management System                 |
+----------------------------------------------------+
| Copyright 2007 Harro "WanWizard" Verton, Exite BV  |
| for support, please visit http://exitecms.exite.eu |
+----------------------------------------------------+
| Some portions copyright 2002 - 2006 Nick Jones     |
| http://www.php-fusion.co.uk/                       |
+----------------------------------------------------+
| Released under the terms & conditions of v2 of the |
| GNU General Public License. For details refer to   |
| the included gpl.txt file or visit http://gnu.org  |
+----------------------------------------------------*/

/*---------------------------------------------------+
| mySQL database functions
+----------------------------------------------------*/
function dbconnect($db_host, $db_user, $db_pass, $db_name) {
	$db_connect = @mysql_connect($db_host, $db_user, $db_pass);
	$db_select = @mysql_select_db($db_name);
	if (!$db_connect) {
		die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to establish connection to MySQL</b><br />".mysql_errno()." : ".mysql_error()."</div>");
	} elseif (!$db_select) {
		die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to select MySQL database</b><br />".mysql_errno()." : ".mysql_error()."</div>");
	}
}

function dbquery($query) {
	$result = @mysql_query($query);
	if (!$result) {
		echo mysql_error();
		return false;
	} else {
		return $result;
	}
}

function dbarray($resource) {
	$result = @mysql_fetch_assoc($resource);
	if (!$result) {
		echo mysql_error();
		return false;
	} else {
		return $result;
	}
}

// to execute "upgrade rev files" compatible database commands
function dbcommands($cmdarray, $db_prefix) {

	// make sure an array is passed
	if (!is_array($cmdarray)) return false;

	// process the commands
	foreach ($cmdarray as $cmd) {

		// skip empty or invalid entries
		if (!is_array($cmd) || count($cmd) == 0) continue;

		// we only support command type='db' here
		if (!isset($cmd['type']) || $cmd['type'] != "db") continue;
		
		// put the correct prefix in place and execute the command
		$result = dbquery(str_replace('##PREFIX##', $db_prefix, $cmd['value']));
	}
	
}

/*---------------------------------------------------+
| Strip Input Function, prevents HTML in unwanted places
+----------------------------------------------------*/
function stripinput($text) {
	if (ini_get('magic_quotes_gpc')) $text = stripslashes($text);
	$search = array("\"", "'", "\\", '\"', "\'", "<", ">", "&nbsp;");
	$replace = array("&quot;", "&#39;", "&#92;", "&quot;", "&#39;", "&lt;", "&gt;", " ");
	$text = str_replace($search, $replace, $text);
	return $text;
}

/*---------------------------------------------------+
| Create a list of files or folders and store them in an array
+----------------------------------------------------*/
function makefilelist($folder, $filter, $sort=true, $type="files") {
	$res = array();
	$filter = explode("|", $filter); 
	$temp = opendir($folder);
	while ($file = readdir($temp)) {
		if ($type == "files" && !in_array($file, $filter)) {
			if (!is_dir($folder.$file)) $res[] = $file;
		} elseif ($type == "folders" && !in_array($file, $filter)) {
			if (is_dir($folder.$file)) $res[] = $file;
		}
	}
	closedir($temp);
	if ($sort) sort($res);
	return $res;
}

/*---------------------------------------------------+
| setup main code starts here
+----------------------------------------------------*/

// absolute path definitions
define("PATH_ROOT", dirname(__FILE__).'/');
define("PATH_ADMIN", PATH_ROOT."administration/");
define("PATH_THEMES", PATH_ROOT."themes/");
define("PATH_THEME", PATH_ROOT."themes/ExiteCMS/");
define("PATH_PHOTOS", PATH_ROOT."images/photoalbum/");
define("PATH_IMAGES", PATH_ROOT."images/");
define("PATH_IMAGES_A", PATH_IMAGES."articles/");
define("PATH_IMAGES_ADS", PATH_IMAGES."advertising/");
define("PATH_IMAGES_AV", PATH_IMAGES."avatars/");
define("PATH_IMAGES_N", PATH_IMAGES."news/");
define("PATH_IMAGES_NC", PATH_IMAGES."news_cats/");
define("PATH_IMAGES_DC", PATH_IMAGES."download_cats/");
define("PATH_INCLUDES", PATH_ROOT."includes/");
define("PATH_MODULES", PATH_ROOT."modules/");
define("PATH_ATTACHMENTS", PATH_ROOT."files/attachments");

define("FUSION_SELF", isset($_SERVER['REDIRECT_URL']) && $_SERVER['REDIRECT_URL'] != "" ? basename($_SERVER['REDIRECT_URL']) : basename($_SERVER['PHP_SELF']));
define('INIT_CMS_OK', true);			

// error tracking
$error = "";

// temp storage for template variables
$variables = array();

// parameter validation
$step = (isset($_GET['step']) ? $_GET['step'] : "0");
$variables['step'] = $step;
$settings = array("locale" => (isset($_GET['localeset']) ? $_GET['localeset'] : "English"));
$variables['localeset'] = $settings['locale'];
$variables['charset'] = "iso-8859-1";

// check if the cache directories are writeable
if (!is_writable(PATH_ATTACHMENTS."cache")) {
	die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to run the ExiteCMS setup: The cache directory is not writeable.</b><br />Please consult the documentation on how to define the proper file rights.</div>");
}
if (!is_writable(PATH_ATTACHMENTS."tplcache")) {
	die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to run the ExiteCMS setup: The template cache directory is not writeable.</b><br />Please consult the documentation on how to define the proper file rights.</div>");
}

// first part in step1: create config.php. We need it later
if ($step == "1") {
	$db_host = stripinput($_POST['db_host']);
	$db_user = stripinput($_POST['db_user']);
	$db_pass = stripinput($_POST['db_pass']);
	$db_name = stripinput($_POST['db_name']);
	$db_prefix = stripinput($_POST['db_prefix']);
	$config = "<?php
// global database settings
"."$"."db_host="."\"".$_POST['db_host']."\"".";
"."$"."db_user="."\"".$_POST['db_user']."\"".";
"."$"."db_pass="."\"".$_POST['db_pass']."\"".";
"."$"."db_name="."\"".$_POST['db_name']."\"".";
"."$"."db_prefix="."\"".$_POST['db_prefix']."\"".";

// user database settings
"."$"."user_db_host="."\"".$_POST['db_host']."\"".";
"."$"."user_db_user="."\"".$_POST['db_user']."\"".";
"."$"."user_db_pass="."\"".$_POST['db_pass']."\"".";
"."$"."user_db_name="."\"".$_POST['db_name']."\"".";
"."$"."user_db_prefix="."\"".$_POST['db_prefix']."\"".";
?>";
	@rename(PATH_ROOT."config.def", PATH_ROOT."config.php");
	$temp = fopen(PATH_ROOT."config.php","w");
	if (!fwrite($temp, $config)) {
		$error .= $locale['430']."<br /><br />";
		fclose($temp);
	} else {
		fclose($temp);
	}
}

require_once PATH_ROOT."includes/theme_functions.php";
require_once PATH_ROOT."includes/locale_functions.php";

// load the locale for this module
locale_load("main.setup");

// process the different setup steps
switch($step) {
	case "0":
		// if the config file already exists, bail out
		if (file_exists(PATH_ROOT."config.php") && filesize(PATH_ROOT."config.php")) {
			die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to run the ExiteCMS setup: A valid configuration exists.</b><br />Please consult the documentation on how to rerun the setup.</div>");
		}
		// check if the config template exists
		if (!file_exists(PATH_ROOT."config.def")) {
			die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to run the ExiteCMS setup: The configuration template file is missing.</b><br />Please reinstall ExiteCMS.</div>");
		}
		// create a list of available locales
		$locale_files = makefilelist("locale/", ".|..", true, "folders");
		$variables['locale_files'] = $locale_files;
		// check if all required directories are writable
		$permissions = "";
		if (!is_writable(PATH_IMAGES)) $permissions .= PATH_IMAGES . "<br />";
		if (!is_writable(PATH_IMAGES_A)) $permissions .= PATH_IMAGES_A . "<br />";
		if (!is_writable(PATH_IMAGES_AV)) $permissions .= PATH_IMAGES_AV . "<br />";
		if (!is_writable(PATH_IMAGES_N)) $permissions .= PATH_IMAGES_N . "<br />";
		if (!is_writable(PATH_ATTACHMENTS)) $permissions .= PATH_ATTACHMENTS . "<br />";
		if (!is_writable("config.def")) {
			$permissions .= "Configuration Template" . "<br />";
		}
		if ($permissions == "") {
			$variables['write_check'] = true; 
		} else { 
			$variables['write_check'] = false;
			$error = "<b>".$locale['412']."</b><br /><br />".$permissions."<br /><b>".$locale['413']."</b>";
		}
		break;
	case "1":
		if ($error == "") {
			require_once "config.php";
			$link = dbconnect($db_host, $db_user, $db_pass, $db_name);
			require_once PATH_INCLUDES."dbsetup_include.php";
			if (isset($fail) && $fail == "1") {
				$variables['fail'] = true;
				$fs = "";
				foreach($failed as $ft) {
					$fs .= ($fs == "" ? "" : ", ") . "'". $ft . "'";
				}
				$error .= sprintf($locale['431'], $fs)."<br /><br />";
			} else {
				$variables['fail'] = false;
			}
		}
		break;
	case "2":
		require_once "config.php";
		$link = dbconnect($db_host, $db_user, $db_pass, $db_name);
		$basedir = substr($_SERVER['PHP_SELF'], 0, -9);
		$username = stripinput($_POST['username']);
		$password1 = stripinput($_POST['password1']);
		$password2 = stripinput($_POST['password2']);
		$email = stripinput($_POST['email']);
		if (!preg_match("/^[-0-9A-Z_@\s]+$/i", $username)) $error .= $locale['450']."<br /><br />\n";
		if (preg_match("/^[0-9A-Z@]{6,20}$/i", $password1)) {
			if ($password1 != $password2) $error .= $locale['451']."<br /><br />\n";
		} else {
			$error .= $locale['452']."<br /><br />\n";
		}
	 	if (!preg_match("/^[-0-9A-Z_\.]{1,50}@([-0-9A-Z_\.]+\.){1,50}([0-9A-Z]){2,4}$/i", $email)) {
			$error .= $locale['453']."<br /><br />\n";
		}
		$password = md5(md5($password1));

		require_once PATH_INCLUDES."dbsetup_include.php";

		if ($error == "") {

			// add records to the CMSconfig table
			$commands = array();		
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('sitename', 'ExiteCMS Powered Website')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('siteurl', '/')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('siteemail', 'webmaster@yourdomain.com')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('siteusername', '$username')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('siteintro', '<center>ExiteCMS v7.0 &copy;2007 Exite BV.<br />See http://exitecms.exite.eu for more information</center>')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('description', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('keywords', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('footer', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('opening_page', 'news.php')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('news_headline', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('news_columns', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('news_items', '3')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('news_latest', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('locale', 'English')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('theme', 'ExiteCMS')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('shortdate', '%d/%m/%Y %H:%M')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('longdate', '%B %d %Y %H:%M:%S')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('forumdate', '%d-%m-%Y %H:%M')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('subheaderdate', '%B %d %Y %H:%M:%S')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('timeoffset', '+0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('numofthreads', '10')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('attachments', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('attachmax', '10485760')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('attachtypes', '.exe,.com,.bat,.js,.htm,.html,.shtml,.php,.php3,.esml,.psd,.mvi')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('thread_notify', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('enable_registration', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('email_verification', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('admin_activation', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('display_validation', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('validation_method', 'image')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('thumb_w', '150')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('thumb_h', '150')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('photo_w', '400')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('photo_h', '300')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('photo_max_w', '1800')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('photo_max_h', '1600')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('photo_max_b', '150000')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('thumb_compression', 'gd2')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('thumbs_per_row', '4')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('thumbs_per_page', '12')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('tinymce_enabled', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('smtp_host', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('smtp_username', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('smtp_password', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('bad_words_enabled', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('bad_words', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('bad_word_replace', '[censored]')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('guestposts', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('numofshouts', '5')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('flood_interval', '15')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('counter', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('max_users', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('max_users_datestamp', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('version', '7.00')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('revision', '955')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('remote_stats', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('maintenance', '0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('maintenance_message', '')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('maintenance_color', '#9C0204')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('forum_flags', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('forum_max_w', '600')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('forum_max_h', '600')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('newsletter_email', 'noreply@yourdomain.com')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('pm_inbox', '100')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('pm_sentbox', '100')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('pm_savebox', '200')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('pm_send2group', '103')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('pm_hide_rcpts', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##CMSconfig (cfg_name, cfg_value) VALUES ('download_columns', '1')");
			$result = dbcommands($commands, $db_prefix);

			// add records to the locale table
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locale (locale_code, locale_name, locale_active, locale_locale, locale_charset) VALUES ('en', 'English', 1, 'en_US|en_GB|english|eng', 'iso-8859-1')");
			$result = dbcommands($commands, $db_prefix);

			// add records to the admin table
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('A',  'articles.gif', '".$locale['462']."', 'articles.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('AC', 'article_cats.gif', '".$locale['461']."', 'article_cats.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('AD', 'admins.gif', '".$locale['460']."', 'administrators.php', 2)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('B',  'blacklist.gif', '".$locale['463']."', 'blacklist.php', 2)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('C',  '', '".$locale['464']."', 'reserved', 2)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('CP', 'c-pages.gif', '".$locale['465']."', 'custom_pages.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('D',  'dl.gif', '".$locale['468']."', 'downloads.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('DB', 'db_backup.gif', '".$locale['466']."', 'db_backup.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('DC', 'dl_cats.gif', '".$locale['467']."', 'download_cats.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('F',  'forums.gif', '".$locale['470']."', 'forums.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('FQ', 'faq.gif', '".$locale['469']."', 'faq.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('I',  'modules.gif', '".$locale['472']."', 'modules.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('IM', 'images.gif', '".$locale['471']."', 'images.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('IP', '', '".$locale['473']."', 'reserved', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('M',  'members.gif', '".$locale['474']."', 'members.php', 2)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('N',  'news.gif', '".$locale['475']."', 'news.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('NC', 'news_cats.gif', '".$locale['494']."', 'news_cats.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('P',  'panels.gif', '".$locale['476']."', 'panels.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('PI', 'phpinfo.gif', '".$locale['478']."', 'phpinfo.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('PO', 'polls.gif', '".$locale['479']."', 'forum_polls.php', 1)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S1', 'settings.gif', '".$locale['487']."', 'settings_main.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S2', 'settings_time.gif', '".$locale['488']."', 'settings_time.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S3', 'settings_forum.gif', '".$locale['489']."', 'settings_forum.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S4', 'registration.gif', '".$locale['490']."', 'settings_registration.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S6', 'settings_misc.gif', '".$locale['492']."', 'settings_misc.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S7', 'settings_pm.gif', '".$locale['493']."', 'settings_messages.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('S8', 'settings_lang.gif', '".$locale['459']."', 'settings_languages.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('SL', 'site_links.gif', '".$locale['481']."', 'site_links.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('T',  'tools.gif', '".$locale['495']."', 'tools.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('U',  'upgrade.gif', '".$locale['483']."', 'upgrade.php', 3)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('UG', 'user_groups.gif', '".$locale['484']."', 'user_groups.php', 2)");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('UR', 'submissions.gif', '".$locale['496']."', 'redirects.php', 1)");
			$result = dbcommands($commands, $db_prefix);

			// add records to the custom pages table
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##custom_pages (page_id, page_title, page_access, page_content, page_allow_comments, page_allow_ratings) VALUES (0, '404 Error Page', 0, '".mysql_escape_string("<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\" align=\"center\"> <tbody><tr><td width=\"10\"> </td><td><div align=\"center\"><font size=\"6\"><span class=\"shoutboxname\"><br />404 - Page Not Found</span><br /></font></div><br /><br /><hr width=\"90%\" size=\"2\" /><br /><br /><div align=\"center\">".$locale['560']."<br /></div><br /><div align=\"center\">".$locale['561']."<br /></div><br /><div align=\"center\">".$locale['562']."<br /></div><br /><br /><hr width=\"90%\" size=\"2\" /><br /><br /><div align=\"center\">".$locale['563']."<br /></div><br /><div align=\"center\">".$locale['564']."</div></td><td width=\"10\"> </td></tr></tbody></table><br />")."', 0, 0)");
			$result = dbcommands($commands, $db_prefix);

			// create the admin rights field for the webmaster, based on all admin modules just inserted
			$result = dbquery("SELECT admin_rights FROM ".$db_prefix."admin");
			$adminrights = "";
			while ($data = dbarray($result)) {
				$adminrights .= ($adminrights == "" ? "" : ".") . $data['admin_rights'];
			}
					
			// add the webmaster to the users table
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##users (user_name, user_password, user_webmaster, user_email, user_hide_email, user_location, user_birthdate, user_aim, user_icq, user_msn, user_yahoo, user_web, user_forum_fullscreen, user_theme, user_offset, user_avatar, user_sig, user_posts, user_joined, user_lastvisit, user_ip, user_rights, user_groups, user_level, user_status) VALUES ('$username', '$password', '1', '$email', '1', '', '0000-00-00', '', '', '', '', '', '0', 'Default', '0', '', '', '0', '".time()."', '0', '0.0.0.0', '".$adminrights."', '', '103', '0')");
			$result = dbcommands($commands, $db_prefix);
	
			// add the default private messages configuration
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##pm_config (user_id, pmconfig_save_sent, pmconfig_read_notify, pmconfig_email_notify, pmconfig_auto_archive ) VALUES ('0', '0', '1', '0', '90')");
			$result = dbcommands($commands, $db_prefix);
		
			// add the default news categories 
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['540']."', 'bugs.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['541']."', 'downloads.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['542']."', 'games.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['543']."', 'graphics.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['544']."', 'hardware.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['545']."', 'journal.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['546']."', 'members.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['547']."', 'mods.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['548']."', 'movies.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['549']."', 'network.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['550']."', 'news.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['552']."', 'security.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['553']."', 'software.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['554']."', 'themes.gif')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##news_cats (news_cat_name, news_cat_image) VALUES ('".$locale['555']."', 'windows.gif')");
			$result = dbcommands($commands, $db_prefix);
	
			// add the standard modules to make them pre-installed
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##modules (mod_title, mod_folder, mod_version) VALUES ('Main menu panel', 'main_menu_panel', '1.0.0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##modules (mod_title, mod_folder, mod_version) VALUES ('Advanced login panel', 'user_info_panel', '1.0.0')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##modules (mod_title, mod_folder, mod_version) VALUES ('Welcome message panel', 'welcome_message_panel', '1.0.0')");
			$result = dbcommands($commands, $db_prefix);

			// and activate the panels of these modules
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##panels (panel_name, panel_filename, panel_side, panel_order, panel_type, panel_access, panel_display, panel_status) VALUES ('".$locale['520']."', 'main_menu_panel', '1', '1', 'file', '0', '0', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##panels (panel_name, panel_filename, panel_side, panel_order, panel_type, panel_access, panel_display, panel_status) VALUES ('".$locale['524']."', 'welcome_message_panel', '2', '1', 'file', '0', '0', '1')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##panels (panel_name, panel_filename, panel_side, panel_order, panel_type, panel_access, panel_display, panel_status) VALUES ('".$locale['526']."', 'user_info_panel', '4', 1, 'file', '0', '0', '1')");
			$result = dbcommands($commands, $db_prefix);

			// add the default menu links 
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['500']."', 'index.php', '0', '1', '0', '1', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['501']."', 'article_cats.php', '0', '1', '0', '2', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['502']."', 'downloads.php', '0', '1', '0', '3', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['503']."', 'faq.php', '0', '1', '0', '4', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['504']."', 'forum/index.php', '0', '1', '0', '5', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['505']."', 'news_cats.php', '0', '1', '0', '6', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['506']."', 'contact.php', '0', '1', '0', '7', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['507']."', 'search.php', '0', '1', '0', '8', 'main_menu_panel')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##site_links (link_name, link_url, link_visibility, link_position, link_window, link_order, panel_name) VALUES ('".$locale['508']."', 'register.php', '100', '2', '0', '9', 'main_menu_panel')");
			$result = dbcommands($commands, $db_prefix);

			// add the default forum poll settings
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##forum_poll_settings (forum_id, enable_polls, create_permissions, vote_permissions, guest_permissions, require_approval, lock_threads, option_max, option_show, option_increment, duration_min, duration_max, hide_poll) VALUES ('0', '1', 'G101', 'G101', '0', '0', '0', '10', '5', '5', '86400', '0', '1')");
			$result = dbcommands($commands, $db_prefix);

			// add the country and currency information to the locales table
			$commands = array();
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', '--', 'Country unknown', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ad', 'Andorra', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ae', 'United Arab Emirates', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'af', 'Afghanistan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ag', 'Antigua And Barbuda', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ai', 'Anguilla', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'al', 'Albania', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'am', 'Armenia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'an', 'Netherlands Antilles', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ao', 'Angola', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ar', 'Argentina', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'as', 'American Samoa', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'at', 'Austria', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'au', 'Australia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'aw', 'Aruba', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'az', 'Azerbaijan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ba', 'Bosnia And Herzegovina', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bb', 'Barbados', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bd', 'Bangladesh', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'be', 'Belgium', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bf', 'Burkina Faso', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bg', 'Bulgaria', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bh', 'Bahrain', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bi', 'Burundi', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bj', 'Benin', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bm', 'Bermuda', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bn', 'Brunei Darussalam', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bo', 'Bolivia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'br', 'Brazil', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bs', 'Bahamas', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bt', 'Bhutan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bv', 'Bouvet Island', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bw', 'Botswana', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'by', 'Belarus', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'bz', 'Belize', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ca', 'Canada', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cc', 'Cocos (Keeling) Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cd', 'The Democratic Republic Of The Congo', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cf', 'Central African Republic', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cg', 'Congo', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ch', 'Switzerland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ci', 'Cote D\'Ivoire', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ck', 'Cook Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cl', 'Chile', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cm', 'Cameroon', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cn', 'China', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'co', 'Colombia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cr', 'Costa Rica', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cs', 'Serbia And Montenegro', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cu', 'Cuba', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cv', 'Cape Verde', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cx', 'Christmas Island', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cy', 'Cyprus', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'cz', 'Czech Republic', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'de', 'Germany', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'dj', 'Djibouti', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'dk', 'Denmark', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'dm', 'Dominica', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'do', 'Dominican Republic', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'dz', 'Algeria', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ec', 'Ecuador', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ee', 'Estonia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'eg', 'Egypt', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'eh', 'Western Sahara', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'er', 'Eritrea', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'es', 'Spain', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'et', 'Ethiopia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'fi', 'Finland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'fj', 'Fiji', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'fk', 'Falkland Islands (Malvinas)', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'fm', 'Federated States Of Micronesia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'fo', 'Faroe Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'fr', 'France', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ga', 'Gabon', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gb', 'United Kingdom', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gd', 'Grenada', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ge', 'Georgia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gf', 'French Guiana', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gg', 'Guernsey', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gh', 'Ghana', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gi', 'Gibraltar', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gl', 'Greenland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gm', 'Gambia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gn', 'Guinea', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gp', 'Guadeloupe', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gq', 'Equatorial Guinea', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gr', 'Greece', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gt', 'Guatemala', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gu', 'Guam', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gw', 'Guinea-Bissau', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'gy', 'Guyana', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'hk', 'Hong Kong', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'hm', 'Heard Island And Mcdonald Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'hn', 'Honduras', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'hr', 'Croatia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ht', 'Haiti', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'hu', 'Hungary', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'id', 'Indonesia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ie', 'Ireland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'il', 'Israel', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'im', 'Isle Of Man', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'in', 'India', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'io', 'British Indian Ocean Territory', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'iq', 'Iraq', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ir', 'Islamic Republic Of Iran', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'is', 'Iceland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'it', 'Italy', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'je', 'Jersey', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'jm', 'Jamaica', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'jo', 'Jordan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'jp', 'Japan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ke', 'Kenya', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kg', 'Kyrgyzstan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kh', 'Cambodia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ki', 'Kiribati', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'km', 'Comoros', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kn', 'Saint Kitts And Nevis', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kp', 'Democratic People\'s Republic Of Korea', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kr', 'Republic Of Korea', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ku', 'Kurdistan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kw', 'Kuwait', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ky', 'Cayman Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'kz', 'Kazakhstan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lb', 'Lebanon', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lc', 'Saint Lucia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'li', 'Liechtenstein', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lk', 'Sri Lanka', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lr', 'Liberia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ls', 'Lesotho', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lt', 'Lithuania', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lu', 'Luxembourg', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'lv', 'Latvia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ly', 'Libyan Arab Jamahiriya', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ma', 'Morocco', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mc', 'Monaco', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'md', 'Republic Of Moldova', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mg', 'Madagascar', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mh', 'Marshall Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mk', 'Macedonia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ml', 'Mali', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mm', 'Myanmar', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mn', 'Mongolia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mp', 'Northern Mariana Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mq', 'Martinique', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mr', 'Mauritania', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ms', 'Montserrat', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mt', 'Malta', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mu', 'Mauritius', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mv', 'Maldives', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mw', 'Malawi', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mx', 'Mexico', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'my', 'Malaysia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mz', 'Mozambique', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'na', 'Namibia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'nc', 'New Caledonia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ne', 'Niger', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'nf', 'Norfolk Island', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ng', 'Nigeria', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ni', 'Nicaragua', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'nl', 'The Netherlands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'no', 'Norway', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'np', 'Nepal', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'nr', 'Nauru', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'nu', 'Niue', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'nz', 'New Zealand', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'om', 'Oman', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pa', 'Panama', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pe', 'Peru', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pf', 'French Polynesia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pg', 'Papua New Guinea', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ph', 'Philippines', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pk', 'Pakistan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pl', 'Poland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pm', 'Saint Pierre And Miquelon', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pr', 'Puerto Rico', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pt', 'Portugal', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'pw', 'Palau', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'py', 'Paraguay', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'qa', 'Qatar', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ro', 'Romania', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ru', 'Russian Federation', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'rw', 'Rwanda', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sa', 'Saudi Arabia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sb', 'Solomon Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sc', 'Seychelles', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sd', 'Sudan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'se', 'Sweden', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sg', 'Singapore', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sh', 'Saint Helena', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'si', 'Slovenia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sj', 'Svalbard And Jan Mayen', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sk', 'Slovakia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sl', 'Sierra Leone', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sm', 'San Marino', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sn', 'Senegal', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'so', 'Somalia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sr', 'Suriname', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sv', 'El Salvador', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sy', 'Syrian Arab Republic', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'sz', 'Swaziland', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tc', 'Turks And Caicos Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'td', 'Chad', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tf', 'French Southern Territories', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tg', 'Togo', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'th', 'Thailand', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tj', 'Tajikistan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tk', 'Tokelau', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tl', 'Timor-Leste', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tm', 'Turkmenistan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tn', 'Tunisia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'to', 'Tonga', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tr', 'Turkey', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tt', 'Trinidad And Tobago', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tv', 'Tuvalu', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tw', 'Taiwan, Province Of China', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ua', 'Ukraine', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ug', 'Uganda', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'um', 'United States Minor Outlying Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'us', 'United States', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'uy', 'Uruguay', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'uz', 'Uzbekistan', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'va', 'Holy See (Vatican City State)', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'vc', 'Saint Vincent And The Grenadines', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 've', 'Venezuela', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'vg', 'British Virgin Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'vi', 'U.S. Virgin Islands', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'vn', 'Viet Nam', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'vu', 'Vanuatu', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'wf', 'Wallis And Futuna', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ws', 'Samoa', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ye', 'Yemen', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'yt', 'Mayotte', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'za', 'South Africa', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'zm', 'Zambia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'zw', 'Zimbabwe', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'tz', 'United Republic of Tanzania', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'eu', 'Europe', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ap', 'Asia/Pacific Region', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'rs', 'Serbia', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'mo', 'Macau', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 're', 'Reunion', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'la', 'Lao People\'s Democratic Republic', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'me', 'Montenegro', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'ps', 'Occupied Palestinian Territory', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'aq', 'Antarctica', '1194378860')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countrycode', 'st', 'Sao Tome and Principe', '1194378860')");
			
			// add country codes to the locales table
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Country unknown', '--', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Unlisted country', '??', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Andorra', 'ad', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'United Arab Emirates', 'ae', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Afghanistan', 'af', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Antigua And Barbuda', 'ag', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Anguilla', 'ai', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Albania', 'al', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Armenia', 'am', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Netherlands Antilles', 'an', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Angola', 'ao', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Argentina', 'ar', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'American Samoa', 'as', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Austria', 'at', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Australia', 'au', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Aruba', 'aw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Azerbaijan', 'az', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bosnia And Herzegovina', 'ba', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Barbados', 'bb', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bangladesh', 'bd', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Belgium', 'be', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Burkina Faso', 'bf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bulgaria', 'bg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bahrain', 'bh', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Burundi', 'bi', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Benin', 'bj', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bermuda', 'bm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Brunei Darussalam', 'bn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bolivia', 'bo', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Brazil', 'br', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bahamas', 'bs', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bhutan', 'bt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Bouvet Island', 'bv', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Botswana', 'bw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Belarus', 'by', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Belize', 'bz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Canada', 'ca', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cocos (Keeling) Islands', 'cc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'The Democratic Republic Of The Congo', 'cd', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Central African Republic', 'cf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Congo', 'cg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Switzerland', 'ch', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cote D\'Ivoire', 'ci', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cook Islands', 'ck', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Chile', 'cl', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cameroon', 'cm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'China', 'cn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Colombia', 'co', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Costa Rica', 'cr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Serbia And Montenegro', 'cs', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cuba', 'cu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cape Verde', 'cv', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Christmas Island', 'cx', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cyprus', 'cy', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Czech Republic', 'cz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Germany', 'de', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Djibouti', 'dj', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Denmark', 'dk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Dominica', 'dm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Dominican Republic', 'do', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Algeria', 'dz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Ecuador', 'ec', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Estonia', 'ee', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Egypt', 'eg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Western Sahara', 'eh', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Eritrea', 'er', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Spain', 'es', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Ethiopia', 'et', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Finland', 'fi', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Fiji', 'fj', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Falkland Islands (Malvinas)', 'fk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Federated States Of Micronesia', 'fm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Faroe Islands', 'fo', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'France', 'fr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Gabon', 'ga', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'United Kingdom', 'gb', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Grenada', 'gd', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Georgia', 'ge', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'French Guiana', 'gf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guernsey', 'gg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Ghana', 'gh', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Gibraltar', 'gi', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Greenland', 'gl', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Gambia', 'gm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guinea', 'gn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guadeloupe', 'gp', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Equatorial Guinea', 'gq', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Greece', 'gr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guatemala', 'gt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guam', 'gu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guinea-Bissau', 'gw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Guyana', 'gy', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Hong Kong', 'hk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Heard Island And Mcdonald Islands', 'hm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Honduras', 'hn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Croatia', 'hr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Haiti', 'ht', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Hungary', 'hu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Indonesia', 'id', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Ireland', 'ie', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Israel', 'il', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Isle Of Man', 'im', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'India', 'in', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'British Indian Ocean Territory', 'io', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Iraq', 'iq', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Islamic Republic Of Iran', 'ir', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Iceland', 'is', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Italy', 'it', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Jersey', 'je', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Jamaica', 'jm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Jordan', 'jo', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Japan', 'jp', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Kenya', 'ke', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Kyrgyzstan', 'kg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cambodia', 'kh', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Kiribati', 'ki', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Comoros', 'km', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Saint Kitts And Nevis', 'kn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Democratic People\'s Republic Of Korea', 'kp', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Republic Of Korea', 'kr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Kurdistan', 'ku', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Kuwait', 'kw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Cayman Islands', 'ky', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Kazakhstan', 'kz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Lebanon', 'lb', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Saint Lucia', 'lc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Liechtenstein', 'li', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Sri Lanka', 'lk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Liberia', 'lr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Lesotho', 'ls', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Lithuania', 'lt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Luxembourg', 'lu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Latvia', 'lv', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Libyan Arab Jamahiriya', 'ly', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Morocco', 'ma', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Monaco', 'mc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Republic Of Moldova', 'md', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Madagascar', 'mg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Marshall Islands', 'mh', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Macedonia', 'mk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mali', 'ml', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Myanmar', 'mm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mongolia', 'mn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Northern Mariana Islands', 'mp', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Martinique', 'mq', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mauritania', 'mr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Montserrat', 'ms', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Malta', 'mt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mauritius', 'mu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Maldives', 'mv', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Malawi', 'mw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mexico', 'mx', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Malaysia', 'my', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mozambique', 'mz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Namibia', 'na', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'New Caledonia', 'nc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Niger', 'ne', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Norfolk Island', 'nf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Nigeria', 'ng', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Nicaragua', 'ni', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'The Netherlands', 'nl', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Norway', 'no', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Nepal', 'np', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Nauru', 'nr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Niue', 'nu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'New Zealand', 'nz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Oman', 'om', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Panama', 'pa', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Peru', 'pe', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'French Polynesia', 'pf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Papua New Guinea', 'pg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Philippines', 'ph', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Pakistan', 'pk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Poland', 'pl', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Saint Pierre And Miquelon', 'pm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Puerto Rico', 'pr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Portugal', 'pt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Palau', 'pw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Paraguay', 'py', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Qatar', 'qa', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Romania', 'ro', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Russian Federation', 'ru', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Rwanda', 'rw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Saudi Arabia', 'sa', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Solomon Islands', 'sb', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Seychelles', 'sc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Sudan', 'sd', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Sweden', 'se', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Singapore', 'sg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Saint Helena', 'sh', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Slovenia', 'si', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Svalbard And Jan Mayen', 'sj', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Slovakia', 'sk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Sierra Leone', 'sl', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'San Marino', 'sm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Senegal', 'sn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Somalia', 'so', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Suriname', 'sr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'El Salvador', 'sv', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Syrian Arab Republic', 'sy', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Swaziland', 'sz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Turks And Caicos Islands', 'tc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Chad', 'td', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'French Southern Territories', 'tf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Togo', 'tg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Thailand', 'th', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Tajikistan', 'tj', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Tokelau', 'tk', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Timor-Leste', 'tl', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Turkmenistan', 'tm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Tunisia', 'tn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Tonga', 'to', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Turkey', 'tr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Trinidad And Tobago', 'tt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Tuvalu', 'tv', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Taiwan, Province Of China', 'tw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Ukraine', 'ua', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Uganda', 'ug', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'United States Minor Outlying Islands', 'um', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'United States', 'us', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Uruguay', 'uy', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Uzbekistan', 'uz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Holy See (Vatican City State)', 'va', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Saint Vincent And The Grenadines', 'vc', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Venezuela', 've', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'British Virgin Islands', 'vg', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'U.S. Virgin Islands', 'vi', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Viet Nam', 'vn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Vanuatu', 'vu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Wallis And Futuna', 'wf', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Samoa', 'ws', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Yemen', 'ye', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Mayotte', 'yt', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'South Africa', 'za', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Zambia', 'zm', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Zimbabwe', 'zw', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'u.s.a.', 'us', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'abu dhabi', 'ae', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'azerbaidjan', 'az', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'bosnia herzegovina', 'ba', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'dubai', 'ae', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'dubai emirates', 'ae', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'equator', 'ec', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'iran', 'ir', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'jordania', 'jo', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'libya', 'ly', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'mesopotamia', 'ku', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'montenegro', 'cs', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'russia', 'ru', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'south korea', 'kr', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'serbia', 'cs', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'sharjah emirate', 'ae', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'syria', 'sy', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'vietnam', 'vn', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'undefined', '--', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'United Republic of Tanzania', 'tz', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Europe', 'eu', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Asia/Pacific Region', 'ap', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Serbia', 'rs', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Macau', 'mo', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Reunion', 're', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Lao People\'s Democratic Republic', 'la', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Montenegro', 'me', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Occupied Palestinian Territory', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Antarctica', 'aq', '1194378981')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'countryname', 'Sao Tome and Principe', 'st', '1194378981')");
			
			// add currencycode codes to the locales table
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ad', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ae', 'AED', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'af', 'AFN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ag', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ai', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'al', 'ALL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'am', 'AMD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'an', 'ANG', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ao', 'AOA', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ar', 'ARS', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'as', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'at', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'au', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'aw', 'AWG', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'az', 'AZN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ba', 'BAM', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bb', 'BBD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bd', 'BDT', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'be', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bf', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bg', 'BGN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bh', 'BHD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bi', 'BIF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bj', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bm', 'BMD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bn', 'BND', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bo', 'BOB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'br', 'BRL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bs', 'BSD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bt', 'BTN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bv', 'NOK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bw', 'BWP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'by', 'BYR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'bz', 'BZD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ca', 'CAD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cc', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cd', 'CDF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cf', 'XAF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cg', 'XAF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ch', 'CHF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ci', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ck', 'NZD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cl', 'CLP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cm', 'XAF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cn', 'CNY', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'co', 'COP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cr', 'CRC', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cs', 'CSD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cu', 'CUP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cv', 'CVE', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cx', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cy', 'CYP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'cz', 'CZK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'de', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'dj', 'DJF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'dk', 'DKK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'dm', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'do', 'DOP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'dz', 'DZD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ec', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ee', 'EEK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'eg', 'EGP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'eh', 'MAD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'er', 'ETB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'es', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'et', 'ETB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'fi', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'fj', 'FJD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'fk', 'FKP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'fm', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'fo', 'DKK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'fr', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ga', 'XAF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gb', 'GBP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gd', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ge', 'GEL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gf', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gg', 'GGP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gh', 'GHC', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gi', 'GIP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gl', 'DKK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gm', 'GMD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gn', 'GNF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gp', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gq', 'XAF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gr', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gt', 'GTQ', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gu', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gw', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'gy', 'GYD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'hk', 'HKD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'hm', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'hn', 'HNL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'hr', 'HRK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ht', 'HTG', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'hu', 'HUF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'id', 'IDR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ie', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'il', 'ILS', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'im', 'IMP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'in', 'INR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'io', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'iq', 'IQD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ir', 'IRR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'is', 'ISK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'it', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'je', 'JEP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'jm', 'JMD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'jo', 'JOD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'jp', 'JPY', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ke', 'KES', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kg', 'KGS', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kh', 'KHR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ki', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'km', 'KMF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kn', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kp', 'KPW', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kr', 'KRW', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kw', 'KWD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ky', 'KYD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'kz', 'KZT', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lb', 'LBP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lc', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'li', 'CHF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lk', 'LKR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lr', 'LRD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ls', 'LSL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lt', 'LTL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lu', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'lv', 'LVL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ly', 'LYD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ma', 'MAD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mc', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'md', 'MDL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mg', 'MGA', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mh', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mk', 'MKD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ml', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mm', 'MMK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mn', 'MNT', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mp', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mq', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mr', 'MRO', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ms', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mt', 'MTL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mu', 'MUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mv', 'MVR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mw', 'MWK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mx', 'MXN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'my', 'MYR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'mz', 'MZM', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'na', 'NAD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'nc', 'XPF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ne', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'nf', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ng', 'NGN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ni', 'NIO', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'nl', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'no', 'NOK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'np', 'NPR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'nr', 'AUD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'nu', 'NZD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'nz', 'NZD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'om', 'OMR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pa', 'PAB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pe', 'PEN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pf', 'XPF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pg', 'PGK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ph', 'PHP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pk', 'PKR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pl', 'PLN', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pm', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pr', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pt', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'pw', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'py', 'PYG', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'qa', 'QAR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ro', 'RON', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ru', 'RUB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'rw', 'RWF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sa', 'SAR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sb', 'SBD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sc', 'SCR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sd', 'SDD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'se', 'SEK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sg', 'SGD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sh', 'SHP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'si', 'SIT', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sj', 'NOK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sk', 'SKK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sl', 'SLL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sm', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sn', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'so', 'SOS', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sr', 'SRD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sv', 'SVC', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sy', 'SYP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'sz', 'SZL', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tc', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'td', 'XAF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tf', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tg', 'XOF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'th', 'THB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tj', 'RUB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tk', 'NZD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tl', 'IDR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tm', 'TMM', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tn', 'TND', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'to', 'TOP', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tr', 'TRY', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tt', 'TTD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tv', 'TVD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'tw', 'TWD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ua', 'UAH', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ug', 'UGX', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'um', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'us', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'uy', 'UYU', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'uz', 'UZS', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'va', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'vc', 'XCD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 've', 'VEB', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'vg', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'vi', 'USD', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'vn', 'VND', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'vu', 'VUV', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'wf', 'XPF', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ws', 'WST', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'ye', 'YER', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'yt', 'EUR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'za', 'ZAR', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'zm', 'ZMK', '1194379127')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currencycode', 'zw', 'ZWD', '1194379127')");
			
			// add currency's to the locales table
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ad', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ae', 'Dirhams', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'af', 'Afghanis', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ag', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ai', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'al', 'Leke', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'am', 'Drams', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'an', 'Netherlands Antilles Guilders (aka Florins)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ao', 'Kwanza', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ar', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'as', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'at', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'au', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'aw', 'Guilders (aka Florins)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'az', 'New Manats', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ba', 'Convertible Marka', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bb', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bd', 'Taka', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'be', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bf', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bg', 'Leva', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bh', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bi', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bj', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bm', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bn', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bo', 'Bolivianos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'br', 'Real', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bs', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bt', 'Ngultrum', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bv', 'Norwegian Kroner', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bw', 'Pulas', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'by', 'Rubles', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'bz', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ca', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cc', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cd', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cf', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cg', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ch', 'Swiss Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ci', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ck', 'New Zealand Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cl', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cm', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cn', 'Yuan Renminbi', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'co', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cr', 'Colones', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cs', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cu', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cv', 'Escudos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cx', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cy', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'cz', 'Koruny', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'de', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'dj', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'dk', 'Kroner', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'dm', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'do', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'dz', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ec', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ee', 'Krooni', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'eg', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'eh', 'Dirhams', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'er', 'Ethiopian Birr', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'es', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'et', 'Ethiopian Birr', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'fi', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'fj', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'fk', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'fm', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'fo', 'Kroner', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'fr', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ga', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gb', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gd', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ge', 'Lari', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gf', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gg', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gh', 'Cedis', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gi', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gl', 'Kroner', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gm', 'Dalasi', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gn', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gp', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gq', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gr', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gt', 'Quetzales', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gu', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gw', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'gy', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'hk', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'hm', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'hn', 'Lempiras', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'hr', 'Kuna', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ht', 'Gourdes', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'hu', 'Forint', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'id', 'Indonesian Rupiahs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ie', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'il', 'New Shekels', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'im', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'in', 'Indian Rupees', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'io', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'iq', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ir', 'Rials', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'is', 'Kronur', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'it', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'je', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'jm', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'jo', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'jp', 'Yen', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ke', 'Shillings', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kg', 'Soms', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kh', 'Riels', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ki', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'km', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kn', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kp', 'Won', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kr', 'Won', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kw', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ky', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'kz', 'Tenge', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lb', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lc', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'li', 'Swiss Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lk', 'Rupees', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lr', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ls', 'Maloti', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lt', 'Litai', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lu', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'lv', 'Lati', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ly', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ma', 'Dirhams', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mc', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'md', 'Lei', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mg', 'Ariary', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mh', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mk', 'Denars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ml', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mm', 'Kyats', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mn', 'Tugriks', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mp', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mq', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mr', 'Ouguiyas', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ms', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mt', 'Liri', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mu', 'Rupees', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mv', 'Rufiyaa', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mw', 'Kwachas', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mx', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'my', 'Ringgits', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'mz', 'Meticais', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'na', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'nc', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ne', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'nf', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ng', 'Nairas', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ni', 'Cordobas', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'nl', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'no', 'Norway Kroner', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'np', 'Rupees', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'nr', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'nu', 'New Zealand Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'nz', 'New Zealand Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'om', 'Rials', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pa', 'Balboa', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pe', 'Nuevos Soles', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pf', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pg', 'Kina', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ph', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pk', 'Rupees', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pl', 'Zlotych', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pm', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pr', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pt', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'pw', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'py', 'Guarani', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'qa', 'Rials', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ro', 'New Lei', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ru', 'Rubles', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'rw', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sa', 'Riyals', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sb', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sc', 'Rupees', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sd', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'se', 'Kronor', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sg', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sh', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'si', 'Tolars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sj', 'Norwegain Kroner', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sk', 'Koruny', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sl', 'Leones', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sm', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sn', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'so', 'Shillings', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sr', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sv', 'Colones', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sy', 'Pounds', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'sz', 'Emalangeni', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tc', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'td', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tf', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tg', 'Communaut� Financi�re Africaine Francs (BCEA)', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'th', 'Baht', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tj', 'Rubles', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tk', 'New Zealand Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tl', 'Indonesia Rupiahs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tm', 'Manats', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tn', 'Dinars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'to', 'Pa\'anga', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tr', 'New Lira', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tt', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tv', 'Tuvalu Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'tw', 'New Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ua', 'Hryvnia', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ug', 'Shillings', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'um', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'us', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'uy', 'Pesos', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'uz', 'Sums', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'va', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'vc', 'East Caribbean Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 've', 'Bolivares', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'vg', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'vi', 'Dollars', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'vn', 'Dong', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'vu', 'Vatu', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'wf', 'Francs', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ws', 'Tala', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'ye', 'Rials', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'yt', 'Euro', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'za', 'South African Rand', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'zm', 'Kwacha', '1194379262')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'currency', 'zw', 'Zimbabwian Dollars', '1194379262')");
				
			// add the charsets to the locale table
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-6', 'Arabic (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-4', 'Baltic (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-2', 'Central European (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'big5', 'Chinese Traditional (Big5)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-5', 'Cyrillic (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'koi8-r', 'Cyrillic (KOI8-R)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'koi8-u', 'Cyrillic (KOI8-U)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-7', 'Greek (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-8-i', 'Hebrew (ISO-Logical)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-8', 'Hebrew (ISO-Visual)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'euc-jp', 'Japanese (EUC)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-2022-jp', 'Japanese (JIS)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-2022-jp', 'Japanese (JIS-Allow 1 byte Kana - SO/SI)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'csISO2022JP', 'Japanese (JIS-Allow 1 byte Kana)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'shift_jis', 'Japanese (Shift-JIS)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'ks_c_5601-1987', 'Korean', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'euc-kr', 'Korean (EUC)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-2022-kr', 'Korean (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-3', 'Latin 3 (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-15', 'Latin 9 (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-9', 'Turkish (ISO)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'utf-7', 'Unicode (UTF-7)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'utf-8', 'Unicode (UTF-8)', '1194384538')");
			$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##locales (locales_locale, locales_name, locales_key, locales_value, locales_datestamp) VALUES ('English', 'charsets', 'iso-8859-1', 'Western European (ISO)', '1194384538')");
			$result = dbcommands($commands, $db_prefix);

			$message = $locale['580'];
		}
		break;
}

if (isset($message)) $variables['message'] = $message;
$variables['error'] = $error;

// define the setup body panel variables
$template_panels[] = array('type' => 'body', 'name' => 'setup', 'template' => 'main.setup.tpl', 'locale' => "main.setup");
$template_variables['setup'] = $variables;

load_templates('body', '');

// close the database connection
@mysql_close();
?>