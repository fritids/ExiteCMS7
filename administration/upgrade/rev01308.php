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
$_revision = '1308';

if (eregi("rev".substr("00000".$_revision,-5).".php", $_SERVER['PHP_SELF']) || !defined('INIT_CMS_OK')) die();

// make sure the required array's exist
if (!isset($revisions) || !is_array($revisions)) $revisions = array();
if (!isset($commands) || !is_array($commands)) $commands = array();

// register this revision update
$revisions[] = array('revision' => $_revision, 
					'date' => mktime(11,00,0,2,27,2008), 
					'title' => "Required updates for ExiteCMS v7.0 rev.".$_revision,
					'description' => "Updated the threads_read table to track the oldest read post as well");

// array to store the commands of this update
$commands = array();

// update the threads_read table
$commands[] = array('type' => 'db', 'value' => "ALTER TABLE ".$db_prefix."threads_read CHANGE thread_page thread_first_read INT(10) UNSIGNED NOT NULL DEFAULT '9999999999'");
$commands[] = array('type' => 'db', 'value' => "ALTER TABLE ".$db_prefix."threads_read CHANGE thread_last_read thread_last_read INT(10) UNSIGNED NOT NULL DEFAULT '0'");

?>