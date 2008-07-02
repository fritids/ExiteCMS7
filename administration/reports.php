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
require_once dirname(__FILE__)."/../includes/core_functions.php";
require_once PATH_ROOT."/includes/theme_functions.php";

// load the locale for this module
locale_load("admin.reports");

// temp storage for template variables
$variables = array();

//check if the user has a right to be here. If not, bail out
if (!checkrights("R") || !defined("iAUTH") || $aid != iAUTH) fallback(BASEDIR."index.php");

// check if the action variable is defined, if not, assign a default
if (!isset($action)) $action = "";

// check if the report_id variable is defined, if not, assign a default
if (!isset($report_id) || !isNum($report_id)) $report_id = 0;

// process the action requested
switch ($action) {

	case "add":
		$variables['report'] = array(
			'report_id' => 0,
			'report_mod_id' => 0,
			'report_name' => "",
			'report_title' => "",
			'report_version' => "",
			'report_active' => 0,
			'report_visibility' => 103
		);
		break;

	case "edit":
		$result = dbquery("SELECT * FROM ".$db_prefix."reports WHERE report_id = '".$report_id."'");
		if ($variables['report'] = dbarray($result)) {
			_debug($variables, true);
			// found the record
		} else {
			// return to the overview screen
			$action = "";
		}
		break;

	case "setstatus":
		// if a status is passed, validate it
		if (isset($status) && isNum($status) && $status >= 0 && $status <= 1) {
			$result = dbquery("UPDATE ".$db_prefix."reports SET report_active = '".$status."' WHERE report_id = '".$report_id."'");
		}
		// return to the overview screen
		$action = "";
		break;
}

// no action specified: show the report overview
if ($action == "") {
	// generate the report overview
	$reports = array();
	$reportindex = array();
	$result = dbquery("SELECT r.*, m.mod_folder FROM ".$db_prefix."reports r LEFT JOIN ".$db_prefix."modules m ON r.report_mod_id = m.mod_id");
	while ($data = dbarray($result)) {
		// get the title for this report
		if ($data['report_mod_id']) {
			locale_load("modules.".$data['mod_folder']);
			$data['report_title'] = $locale[$data['report_title']];
		} else {
			// make sure this field is not NULL
			$data['mod_folder'] = "";
		}
		$data['groupname'] = getgroupname($data['report_visibility']);
			// store the report record
		$reports[$data['report_id']] = $data;
		$reportindex[] = $data['report_title']."_>_".$data['report_id'];
	}
	//make sure the modules are properly sorted
	sort($reportindex);
	$variables['reports'] = array();
	foreach($reportindex as $index) {
		$variables['reports'][] = $reports[substr(strstr($index,"_>_"),3)];
	}
	// reload the locale for this module
	locale_load("admin.reports");
}

//_debug($variables, true);

// define the admin body panel
$template_panels[] = array('type' => 'body', 'name' => 'admin.reports', 'template' => 'admin.reports.tpl', 'locale' => "admin.reports");
$template_variables['admin.reports'] = $variables;

// Call the theme code to generate the output for this webpage
require_once PATH_THEME."/theme.php";
?>