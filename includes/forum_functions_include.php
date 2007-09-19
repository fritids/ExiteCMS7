<?php
/*---------------------------------------------------+
| PLi-Fusion Content Management System               |
+----------------------------------------------------+
| Copyright 2007 WanWizard (wanwizard@gmail.com)     |
| http://www.pli-images.org/pli-fusion               |
+----------------------------------------------------+
| Some portions copyright ? 2002 - 2006 Nick Jones   |
| http://www.php-fusion.co.uk/                       |
| Released under the terms & conditions of v2 of the |
| GNU General Public License. For details refer to   |
| the included gpl.txt file or visit http://gnu.org  |
+----------------------------------------------------*/
if (eregi("forum_functions_include.php", $_SERVER['PHP_SELF']) || !defined('IN_FUSION')) die();

// add a poll vote to the database
function fpm_vote() {
	global $db_prefix, $userdata, $fpm, $fpm_settings;

	if (iMEMBER && FPM_ACCESS && $fpm['poll_id'] != 0 && $fpm['vote_selection'] != 0) {
		$vote_count = dbcount("(poll_id)", "forum_poll_votes", "poll_id='".$fpm['poll_id']."' AND user_id='".$userdata['user_id']."'");
		if ($vote_count == 0) {
			$result = dbquery("INSERT INTO ".$db_prefix."forum_poll_votes (poll_id, user_id, vote_selection)
				VALUES ('".$fpm['poll_id']."', '".$userdata['user_id']."', '".$fpm['vote_selection']."')"
			);
		}
	}
}

function fpm_delete() {
	global $db_prefix, $fpm;

	if (FPM_ACCESS && isset($fpm['poll_id']) && isNum($fpm['poll_id'])) {
		$result = dbquery("DELETE FROM ".$db_prefix."forum_polls WHERE poll_id='".$fpm['poll_id']."'");
		$result = dbquery("DELETE FROM ".$db_prefix."forum_poll_options WHERE poll_id='".$fpm['poll_id']."'");
		$result = dbquery("DELETE FROM ".$db_prefix."forum_poll_votes WHERE poll_id='".$fpm['poll_id']."'");
	}
}

function fpm_save($post_id) {
	global $db_prefix, $thread_id, $fpm;

	if (FPM_ACCESS) {
		if (isset($fpm['reset_votes']) && $fpm['reset_votes'] != "" && isset($fpm['poll_id']) && isNum($fpm['poll_id'])) {
			$result = dbquery("DELETE FROM ".$db_prefix."forum_poll_votes WHERE poll_id='".$fpm['poll_id']."'");
		}
		if ($fpm['question'] == "" || $fpm['blank_options'] > ($fpm['option_show'] - 2)) {
			fpm_delete();
		} elseif ($fpm['question'] != "" && $fpm['blank_options'] < ($fpm['option_show'] - 1)) {
			if (isset($fpm['poll_id']) && isNum($fpm['poll_id'])) {
				$result = dbquery("UPDATE ".$db_prefix."forum_polls SET poll_question='".$fpm['question']."',
					poll_start='".$fpm['start']."', poll_end='".$fpm['end']."', poll_type='".$fpm['type']."' WHERE poll_id='".$fpm['poll_id']."'"
				);
				$result = dbquery("SELECT * FROM ".$db_prefix."forum_poll_options
					WHERE poll_id='".$fpm['poll_id']."' ORDER BY option_order"
				);
				if (dbrows($result) != 0) {
					$i = 1;
					while($data = dbarray($result)) {
						if ($i <= $fpm['option_show'] && $fpm['option'][$i] != "") {
							$result2 = dbquery("UPDATE ".$db_prefix."forum_poll_options SET option_order='$i',
								option_text='".$fpm['option'][$i]."' WHERE option_id='".$data['option_id']."'"
							);
						} else {
							$result2 = dbquery("DELETE FROM ".$db_prefix."forum_poll_options WHERE option_id='".$data['option_id']."'");
						}
						$i ++;
					}
					while ($i <= $fpm['option_show'] && $fpm['option'][$i] != "") {
						$result = dbquery("INSERT INTO ".$db_prefix."forum_poll_options (option_id, poll_id,
							option_order, option_text) VALUES (NULL, '".$fpm['poll_id']."', '$i', '".$fpm['option'][$i]."')"
						);
						$i ++;
					}
				} else {
					$x = 1;
					for($i = 1; $i <= $fpm['option_show']; $i ++) {
						if ($fpm['option'][$i] != "") {
							$result = dbquery("INSERT INTO ".$db_prefix."forum_poll_options (option_id, poll_id,
								option_order, option_text) VALUES (NULL, '".$fpm['poll_id']."', '$x', '".$fpm['option'][$i]."')"
							);
							$x ++;
						}
					}
				}
			} else {
				$result = dbquery("INSERT INTO ".$db_prefix."forum_polls (poll_id, thread_id, post_id,
					poll_question, poll_start, poll_end, poll_type, poll_status) VALUES (NULL, '$thread_id',
					'$post_id', '".$fpm['question']."', '".$fpm['start']."', '".$fpm['end']."', '".$fpm['type']."', '1')"
				);
				$fpm['poll_id'] = mysql_insert_id(); $x = 1;
				for($i = 1; $i <= $fpm['option_show']; $i ++) {
					if ($fpm['option'][$i] != "") {
						$result = dbquery("INSERT INTO ".$db_prefix."forum_poll_options (option_id, poll_id,
							option_order, option_text) VALUES (NULL, '".$fpm['poll_id']."', '$x', '".$fpm['option'][$i]."')"
						);
						$x ++;
					}
				}
			}
		}
	}
}

function fpm_preview() {
	global $locale, $userdata, $fpm, $fpm_settings, $variables;

	if (FPM_ACCESS && (!isset($fpm['exists']) || $fpm['exists'] == 0) && $fpm['question'] != "" && $fpm['blank_options'] < ($fpm_settings['option_max'] - 1)) {
		$variables['poll'] = array();
		for($i = 1; $i <= $fpm_settings['option_max']; $i ++) {
			if(isset($fpm['option'][$i]) && $fpm['option'][$i] != "") {
				$poll = array('poll_id' => 0, 'option_id' => $i, 'option_order' => $i, 'option_text' => $fpm['option'][$i], 'option_votes' => 0, 'option_results' => 0, 'is_link' => 0);
				$variables['poll'][] = $poll;
			}
		}
		$variables['poll_options'] = array('poll_id' => 0, 'thread_id' => 0, 'post_id' => 0);
		$variables['poll_options']['poll_question'] = $fpm['question'];
		$variables['poll_options']['poll_start'] = $fpm['start'];
		$variables['poll_options']['poll_end'] = $fpm['end'];
		$variables['poll_options']['poll_status'] = 99;
		$variables['poll_options']['post_author'] = $userdata['user_id'];
		$variables['poll_options']['user_name'] = $fpm['user_name'];
		$variables['total_votes'] = 0;
		return true;
	} else {
		return false;
	}
}

// code the generate the poll panel
function fpm_view() {
	global $db_prefix, $locale, $userdata, $forum_id, $thread_id, $fpm_settings,
		$variables;

	if (FPM_ACCESS) {	
		$result = dbquery("SELECT t1.*, t2.post_author, t3.user_name FROM ".$db_prefix."forum_polls t1
			LEFT JOIN ".$db_prefix."posts t2 ON t1.post_id=t2.post_id
			LEFT JOIN ".$db_prefix."users t3 ON t2.post_author=t3.user_id WHERE t1.thread_id='$thread_id'"
		);
		if (dbrows($result) != 0) {
			$data = dbarray($result);
			$variables['poll_options'] = $data;
			$result = dbquery("SELECT * FROM ".$db_prefix."forum_poll_options WHERE poll_id='".$data['poll_id']."' ORDER BY option_order");
			$option_total = dbrows($result);
			if ($option_total > 1) {
				$total_votes = dbcount("(poll_id)", "forum_poll_votes", "poll_id='".$data['poll_id']."'");
				$variables['total_votes'] = $total_votes;
				if (iMEMBER) {
					$vote_count = dbcount("(poll_id)", "forum_poll_votes", "poll_id='".$data['poll_id']."' AND user_id='".$userdata['user_id']."'");
					$voted = $vote_count == 0 ? 0 : 1;
				} else { 
					$voted = $fpm_settings['guest_permissions'] == 1 ? 0 : 1; 
				}
				if ($data['poll_end'] != 0 && $data['poll_end'] <= date("U")) { 
					$voted = 1; 
				}
				$variables['voted'] = $voted;
				$variables['poll'] = array();
				while($data2 = dbarray($result)) {
					$option_votes = dbcount("(poll_id)", "forum_poll_votes", "poll_id='".$data['poll_id']."' AND vote_selection='".$data2['option_id']."'");
					if ($total_votes == 0) { 
						$vote_results = 0; 
					} else { 
						$vote_results = round($option_votes / $total_votes * 100); 
					}
					$data2['option_votes'] = $option_votes;
					$data2['vote_results'] = $vote_results;
					$data2['is_link'] = preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $data2['option_text']);
					// get some extra information based on the poll type
					switch($data['poll_type']) {
						case 0:
							break;
						case 1:
							$result2 = dbquery("SELECT u.user_id, u.user_name FROM ".$db_prefix."users u, ".$db_prefix."forum_poll_votes p WHERE p.poll_id = '".$data['poll_id']."' AND p.vote_selection = '".$data2['option_id']."' and p.user_id = u.user_id");
							$user_votes = array();
							while ($user_vote = dbarray($result2)) {
								$user_votes[] = $user_vote;
							}
							$data2['user_votes'] = $user_votes;
							break;
					}
					$variables['poll'][] = $data2;				
				}
				$variables['poll_ended'] = false;
				if($data['poll_end'] != 0 && $data['poll_end'] <= date("U")) {
					$variables['poll_ended'] = true;
				}
			}
			return true;
		}
	}
	return false;
}

// check if a poll exists for this forum and thread id
function fpm_poll_exists() {
	global $locale, $data;

die('fpm_foll_exists() - needs to be fixed');

	if (FPM_ACCESS) {
		$result = dbcount("(poll_id)", "forum_polls", "thread_id='".$data['thread_id']."'");
		$text = $result != 0 ? " <b>".$locale['FPM_200']."</b>" : "";
		return $text;
	}
}

// check if a poll exists for this forum and thread id
function fpm_panels_poll_exists($forum_id, $thread_id) {
	global $db_prefix, $locale;

	if (isNum($forum_id) && isNum($thread_id)) {
		$fpm_result = dbquery("SELECT * FROM ".$db_prefix."forum_poll_settings WHERE forum_id='$forum_id'");
		if (dbrows($fpm_result) != 0) { $fpm_settings = dbarray($fpm_result); $fpm_settings['forum_exists'] = 1; }
		else { $fpm_settings = dbarray(dbquery("SELECT * FROM ".$db_prefix."forum_poll_settings WHERE forum_id='0'")); }
		if (!defined('FPM_ACCESS')) {
			if (!iMEMBER) {
				if ($fpm_settings['guest_permissions'] != 0) { define("FPM_ACCESS", true); }
			} elseif ($fpm_settings['enable_polls'] == 1 && $fpm_settings['vote_permissions'] != "") {
				$temp_array = explode(".", $fpm_settings['vote_permissions']);
				for($i = 0; $i < count($temp_array); $i ++) {
					if (isNum($temp_array[$i])) {
						if ($userdata['user_id'] == $temp_array[$i]) { define("FPM_ACCESS", true); break; }
					} else {
						if (checkgroup(substr($temp_array[$i], 1))) { define("FPM_ACCESS", true); break; }
					}
				}
			} else { define("FPM_ACCESS", false); }
			if (!defined('FPM_ACCESS')) { define("FPM_ACCESS", false); }
		}
		if (FPM_ACCESS) {
			$result = dbcount("(poll_id)", "forum_polls", "thread_id='$thread_id'");
			if ($result) return true;
		}
	}
	return false;
}

// function to display the action result dialog, and optionally redirect back to the post
function resultdialog($title, $message="", $redirect=true, $backlink=false, $timeout=4000) {

	global $locale, $forum_id, $thread_id, $post_id,
		$template_panels, $template_variables;

	// temp storage for template variables
	$variables = array();

	$variables['message'] = $message;
	$variables['redirect'] = $redirect;
	$variables['backlink'] = $backlink;
	$variables['timeout'] = $timeout;
	$variables['forum_id'] = (isset($forum_id) && isNum($forum_id)) ? $forum_id : false;
	$variables['thread_id'] = (isset($thread_id) && isNum($thread_id)) ? $thread_id : false;
	$variables['post_id'] = (isset($post_id) && isNum($post_id)) ? $post_id : false;

	// define the search body panel variables
	$template_panels[] = array('type' => 'body', 'name' => 'forum.resultdialog', 'title' => $title, 'template' => 'forum.resultdialog.tpl', 
									'locale' => array(PATH_LOCALE.LOCALESET."forum/post.php", PATH_LOCALE.LOCALESET."forum/main.php",PATH_LOCALE.LOCALESET."admin/forum_polls.php"));
	$template_variables['forum.resultdialog'] = $variables;
}

// parse the [code] sections in a post
function parsemessage($rawmsg, $smileys=true) {

	// temp message storage
	$message = "";
	$codeblocks = array();

	// Split off the [code] blocks to exclude them from BBcode parsing
	
	// find the code [code] occurence
	$i = strpos($rawmsg, "[code]");

	// loop through the message until all are found and processed
	while ($i !== false) {
		// strip the bit before the [code] BBcode, and add a placeholder
		$message .= substr($rawmsg, 0, $i+6)."{**@@**}";
		// strip the processed bit
		$rawmsg = substr($rawmsg, $i+6);
		// find the end of the [code] block
		$j = strpos($rawmsg, "[/code]");
		// if not found, add the remaining bit, a forced [/code], and stop processing
		if ($j === false) {
			$message = str_replace("{**@@**}", $rawmsg, $message);
			break;
		}
		// store this code block
		$codeblocks[] = substr($rawmsg, 0, $j);
		// strip the processed bit
		$rawmsg = substr($rawmsg, $j);
		// check if there are more code segments
		$i = strpos($rawmsg, "[code]");
	}

	// any text left?
	if (strlen($rawmsg)) $message .= $rawmsg;

	// find URL's in the text, and convert them to a [url] BBcode
	$message = preg_replace("#(^|\s)(www|WWW)\.([^\s<>\/]+)\/([^\s\r\n<>\)\,\:\;\[]+)#sm", "\\1[url=http://\\2.\\3/\\4]http://\\2.\\3[/url]", $message);
	$message = preg_replace("#(^|[^\"=\]]{1})(http|HTTP|ftp)(s|S)?://([^\s<>\/]+)\/([^\s\r\n<>\)\,\:\;\[]+)#sm", "\\1[url=\\2\\3://\\4/\\5]\\2\\3://\\4[/url]", $message);

	// parse the message, and convert all BBcode found
	if ($smileys) $message = parsesmileys($message);
	$message = parseubb($message);
	$message = nl2br($message);

	// re-insert the saved code blocks
	foreach($codeblocks as $codeblock) {
		// split the codeblock to add linenumbers
		$lines = explode("\n", $codeblock);
		// get rid of empty lines at the beginning and the end of the block
		while (count($lines)>0 && trim($lines[0]) == "") {
			array_shift($lines);
		}
		while (count($lines)>0 && trim($lines[count($lines)-1]) == "") {
			array_pop($lines);
		}
		// only create a code block if there's code to display
		if (count($lines) > 0) {
			// check how wide the number column should be
			$w = strlen(count($lines));
			// embedded div with the linenumbers
			$codeblock = "<div><pre class='codenr'>";
			for($i=0;$i<count($lines);$i++) {
				$codeblock .= str_replace("*", "&nbsp;", str_pad($i+1, $w, "*", STR_PAD_LEFT))."\n";
			}
			$codeblock .= "</pre></div><pre class='code'>";
			// add the lines back to the code div
			foreach($lines as $nr => $line) {
				$codeblock .= $line;
			}
			$codeblock .= "</pre>";
			// find the first placeholder
			$i = strpos($message, "{**@@**}");
			$message = substr($message, 0, $i).$codeblock.substr($message, $i+8);
		} else {
			// no code, remove the code block entirely
			$i = strpos($message, "{**@@**}");
			$message = substr($message, 0, $i-6).substr($message, $i+15);
		}
	}	
	// return the parsed message body
	return $message;
}
?>
