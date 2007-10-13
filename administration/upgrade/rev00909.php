<?php
/*---------------------------------------------------+
| ExiteCMS Content Management System                 |
+----------------------------------------------------+
| Copyright 2007 Harro "WanWizard" Verton, Exite BV  |
| for support, please visit http://exitecms.exite.eu |
+----------------------------------------------------+
| Released under the terms & conditions of v2 of the |
| GNU General Public License. For details refer to   |
| the included gpl.txt file or visit http://gnu.org  |
+----------------------------------------------------*/

// upgrade for revision
$_revision = '909';

if (eregi("rev".substr("00000".$_revision,-5).".php", $_SERVER['PHP_SELF']) || !defined('INIT_CMS_OK')) die();

// make sure the required array's exist
if (!isset($revisions) || !is_array($revisions)) $revisions = array();
if (!isset($commands) || !is_array($commands)) $commands = array();

// register this revision update
$revisions[] = array('revision' => $_revision, 'date' => mktime(22,00,0,10,10,2007), 'description' => "Required updates for ExiteCMS v7.0 rev.".$_revision."<br /><font color='red'>New CMS settings structure, to create more flexibility for modules.</font>");

// array to store the commands of this update
$commands = array();

// database changes

// create new CMSconfig table
$commands[] = array('type' => 'db', 'value' => "CREATE TABLE ##PREFIX##CMSconfig (
  cfg_id smallint(5) unsigned NOT NULL auto_increment,
  cfg_name varchar(25) NOT NULL default '',
  cfg_value TEXT NOT NULL default '',
  PRIMARY KEY  (cfg_id)
) ENGINE=MyISAM;");

// and copy the settings to the new table
$commands[] = array('type' => 'function', 'value' => "migrate_settings");

/*---------------------------------------------------+
| functions required for part of the upgrade process |
+----------------------------------------------------*/
function migrate_settings() {
	global $db_prefix;

	$result = dbquery("SELECT * FROM ".$db_prefix."settings LIMIT 1");
	if ($data = dbarray($result)) {
		foreach($data as $name => $value) {
			$result = dbquery("INSERT INTO ".$db_prefix."CMSconfig (cfg_name, cfg_value) VALUES ('".$name."', '".$value."')");
		}
	}

}
?>