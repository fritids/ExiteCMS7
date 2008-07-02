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
$_revision = '1490';

if (eregi("rev".substr("00000".$_revision,-5).".php", $_SERVER['PHP_SELF']) || !defined('INIT_CMS_OK')) die();

// make sure the required array's exist
if (!isset($revisions) || !is_array($revisions)) $revisions = array();
if (!isset($commands) || !is_array($commands)) $commands = array();

// register this revision update
$revisions[] = array('revision' => $_revision, 
					'date' => mktime(16,00,0,6,28,2008), 
					'title' => "Required updates for ExiteCMS v7.1 rev.".$_revision,
					'description' => "Added Reporting core module.");

// array to store the commands of this update
$commands = array();

// add the new admin module "Reports"
$commands[] = array('type' => 'db', 'value' => "INSERT INTO ##PREFIX##admin (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('R', 'reports.gif', '256', 'reports.php', 3)");

// add the new "Reporting" table
$commands[] = array('type' => 'db', 'value' => "CREATE TABLE ##PREFIX##reports (
  report_id smallint(5) unsigned NOT NULL auto_increment,
  report_mod_id smallint(5) unsigned NOT NULL default 0,
  report_name varchar(100) NOT NULL default '',
  report_title varchar(100) NOT NULL default '',
  report_version varchar(10) NOT NULL default 0,
  report_active tinyint(1) unsigned NOT NULL default 0,
  report_visibility tinyint(3) NOT NULL default 0,
  PRIMARY KEY  (report_id)
) ENGINE=MyISAM");


/*---------------------------------------------------+
| functions required for part of the upgrade process |
+----------------------------------------------------*/
?>