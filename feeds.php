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
require_once dirname(__FILE__)."/includes/core_functions.php";
require_once PATH_ROOT."/includes/theme_functions.php";
require_once PATH_ROOT."/includes/forum_functions_include.php";

// validate the parameters
if (!FUSION_QUERY || !isset($type)) fallback("index.php");

// load the locale for this module
locale_load("main.feeds");

// define how many items we want per RSS feed
define('ITEMS_PER_FEED', $settings['numofthreads']*2);

// define the maximum length of the description field
define('MAX_DESC_LENGTH', 10240);

// check if authentication is valid. If not, reset it
if (isset($_SERVER['PHP_AUTH_USER'])) {
	$result = auth_validate_BasicAuthentication();
	if ($result != 0) unset($_SERVER['PHP_AUTH_USER']);
}

// define the channels and feeds arrays
$channels = array();
$feeds = array();
// process the feed type
switch (strtolower($type)) {
	case "forum":
		// required parameter id, must be numeric
		if (!isset($id) || !isNum($id)) fallback(FORUM."index.php");
		// check if the forum exists
		$result = dbquery("SELECT * FROM ".$db_prefix."forums WHERE forum_id = '$id'");
		if (!$result)
			fallback("index.php");
		else
			$data = dbarray($result);
			if (!$data['forum_cat']) fallback(FORUM."index.php");
			if (!checkgroup($data['forum_access'])) {
				auth_BasicAuthentication();
			}
		// create the channel record for this RSS feed
		$channel = array();
		$channel['title'] = $locale['400']." ".$data['forum_name'];
		$channel['description'] = "<![CDATA[".$data['forum_description']."]]>";
		$channel['link'] = $settings['siteurl']."forum/viewforum.php?forum_id=".$data['forum_id'];
//		$channel['language'] = "";
//		$channel['pubDate'] = "";
//		$channel['lastBuildDate'] = "";
		$channel['generator'] = "ExiteCMS RSS Feed Generator v1.0";
		$channel['webMaster'] = $settings['siteemail']." (Feed Manager)";
		$channels[] = $channel;
		$channel_count = count($channels);
		// create the feed resource
		$feed = array();
		$result = dbquery(
			"SELECT p.*, u.*, u2.user_name AS edit_name FROM ".$db_prefix."posts p
			LEFT JOIN ".$db_prefix."users u ON p.post_author = u.user_id
			LEFT JOIN ".$db_prefix."users u2 ON p.post_edituser = u2.user_id AND post_edituser > '0'
			WHERE p.forum_id='$id' ORDER BY post_datestamp DESC LIMIT ".ITEMS_PER_FEED
		);
		while ($data = dbarray($result)) {
			// filter control characters from the message
			$data['post_message'] = parsemessage(array(), preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', "", $data['post_message']), true, true);
			// create the RSS item
			$item = array();
			$item['title'] = "<![CDATA[ ".$data['post_subject']." ]]>";
			$item['link'] = $settings['siteurl']."forum/viewthread.php?forum_id=".$data['forum_id']."&amp;thread_id=".$data['thread_id']."&amp;pid=".$data['post_id']."#post_".$data['post_id'];
			$item['description'] = "<![CDATA[ <b>".$data['user_name']." ".$locale['401']."</b><br />".(strlen($data['post_message']) > MAX_DESC_LENGTH ? (substr($data['post_message'],0,MAX_DESC_LENGTH-4)." ...") : $data['post_message'])." ]]>";
			// locale must be english for this to work!
			$loc = setlocale(LC_TIME, "en_US");
			$item['pubDate'] = strftime("%a, %d %b %G %T %z", $data['post_datestamp']);
			setlocale(LC_TIME, $loc);
			$item['guid'] = $item['link'];	// make the guid equal to the link, we don't have a need for permalinks
			$feed[] = $item;
		}
		$feeds[] = $feed;
		$feed_count = count($feeds);
		break;
	default:
		fallback(FORUM."index.php");
}

// validate the feed selection, bail out if not correct
if (!isset($channels) || !is_array($channels) || !isset($channel_count) || $channel_count == 0) fallback(FORUM."index.php");
if (!isset($feeds) || !is_array($feeds) || !isset($feed_count) || $feed_count == 0) fallback(FORUM."index.php");

// start building the XML file
//header("Content-type: text/xml; charset=".$settings['charset']);
echo "<?xml version=\"1.0\" encoding=\"".$settings['charset']."\"?>\n";
echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";

// loop through the channels
foreach ($channels as $index => $channel) {
	// opening: channel information
	echo "\t<channel>\n";
	// atom backlink to the channel
	echo "\t\t<atom:link href=\"".$settings['siteurl'].FUSION_SELF."?".FUSION_QUERY."\" rel=\"self\" type=\"application/rss+xml\" />\n";
	foreach ($channel as $tag => $value) {
		echo "\t\t<".$tag.">".$value."</".$tag.">\n";
	}
	// loop through the items
	foreach($feeds[$index] as $feed) {
		// opening: item information
		echo "\t\t<item>\n";
		// loop through the item tags
		foreach ($feed as $tag => $value) {
			echo "\t\t\t<".$tag.">".$value."</".$tag.">\n";
		}
		echo "\t\t</item>\n";
	}
	// close the channel
	echo "\t</channel>\n";
}
// close the rss feed
echo "</rss>\n";
?>
