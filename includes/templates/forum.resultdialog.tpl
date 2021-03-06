{***************************************************************************}
{* ExiteCMS Content Management System                                      *}
{***************************************************************************}
{* Copyright 2006-2008 Exite BV, The Netherlands                           *}
{* for support, please visit http://www.exitecms.org                       *}
{*-------------------------------------------------------------------------*}
{* Released under the terms & conditions of v2 of the GNU General Public   *}
{* License. For details refer to the included gpl.txt file or visit        *}
{* http://gnu.org                                                          *}
{***************************************************************************}
{* $Id::                                                                  $*}
{*-------------------------------------------------------------------------*}
{* Last modified by $Author::                                             $*}
{* Revision number $Rev::                                                 $*}
{***************************************************************************}
{*                                                                         *}
{* Template for the forum modules. Displays a result dialog with a message *}
{* a link back to the forum, and optionally to the thread and post as well *}
{*                                                                         *}
{***************************************************************************}
{include file="_opentable.tpl" name=$_name title=$_title state=$_state style=$_style}
<div align='center'>
	{if $message|default:"" == ""}
		<br />
		<b>{$locale.442}</b>
		<br /><br />
	{else}
		<br />
		<b>{$message}</b>
		<br /><br />
	{/if}
	{if $thread_id|default:0 != 0}
		{buttonlink name=$locale.447 link="viewthread.php?forum_id="|cat:$forum_id|cat:"&amp;thread_id="|cat:$thread_id}&nbsp;
	{/if}
	{buttonlink name=$locale.448 link="viewforum.php?forum_id="|cat:$forum_id}&nbsp;
	{buttonlink name=$locale.449 link="index.php"}
	{if $backlink}
		{buttonlink name=$locale.457 link="javascript: history.go(-1);" script="yes"}
	{/if}
	<br /><br />
</div>
{include file="_closetable.tpl"}
{if $redirect}
	<script type="text/javascript">
	<!--
	function autocontinue() {ldelim}
	{if $post_id}
		window.location = 'viewthread.php?forum_id={$forum_id}&thread_id={$thread_id}&pid={$post_id}#post_{$post_id}';
	{else}
		{if $thread_id}
			window.location = 'viewthread.php?forum_id={$forum_id}&thread_id={$thread_id}';
		{else}
			window.location = 'viewforum.php?forum_id={$forum_id}';
		{/if}
	{/if}
	{rdelim}
	setTimeout('autocontinue()', {$timeout});
	//-->
	</script>
{/if}
{***************************************************************************}
{* End of template                                                         *}
{***************************************************************************}
