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
{* Template for the admin content module 'comments'                        *}
{*                                                                         *}
{***************************************************************************}
{if $comment_id}
	{include file="_opentable.tpl" name=$_name title=$locale.400 state=$_state style=$_style}
	<form name='inputform' method='post' action='{$smarty.const.FUSION_SELF}{$aidlink}&amp;comment_id={$comment_id}&amp;ctype={$ctype}&amp;cid={$cid}'>
		<table align='center' cellpadding='0' cellspacing='0' width='400' >
			<tr>
				<td align='center' class='tbl'>
					<textarea name='comment_message' rows='5' class='textbox' style='width:400px'>{$comment.comment_message}</textarea>
					<br />
					<input type='button' value='b' class='button' style='font-weight:bold;width:25px;' onClick="addText('comment_message', '[b]', '[/b]');" />
					<input type='button' value='i' class='button' style='font-style:italic;width:25px;' onClick="addText('comment_message', '[i]', '[/i]');" />
					<input type='button' value='u' class='button' style='text-decoration:underline;width:25px;' onClick="addText('comment_message', '[u]', '[/u]');" />
					<input type='button' value='url' class='button' style='width:30px;' onClick="addText('comment_message', '[url]', '[/url]');" />
					<input type='button' value='mail' class='button' style='width:35px;' onClick="addText('comment_message', '[mail]', '[/mail]');" />
					<input type='button' value='img' class='button' style='width:30px;' onClick="addText('comment_message', '[img]', '[/img]');" />
					<input type='button' value='center' class='button' style='width:45px;' onClick="addText('comment_message', '[center]', '[/center]');" />
					<input type='button' value='small' class='button' style='width:40px;' onClick="addText('comment_message', '[small]', '[/small]');" />
				</td>
			</tr>
			<tr>
				<td align='center' class='tbl'>
					<input type='checkbox' name='disable_smileys' value='1'{if $comment.comment_smileys} checked{/if}>{$locale.402}
					<br /><br />
					<input type='submit' name='save_comment' value='{$locale.401}' class='button'>
				</td>
			</tr>
		</table>
	</form>
	{include file="_closetable.tpl"}
{/if}
{include file="_opentable.tpl" name=$_name title=$locale.410 state=$_state style=$_style}
{section name=id loop=$comments}
	<table align='center' cellpadding='0' cellspacing='1' width='100%' class='tbl-border'>
		<tr>
			<td class='tbl2'>
				<b>
				{if $comments[id].user_name|default:"" != ""}
					<a href='{$smarty.const.BASEDIR}profile.php?lookup={$comments[id].comment_name}'>{$comments[id].user_name}</a>
				{else}
					{$comments[id].comment_name}
				{/if}
				</b>
				<span class='small'>
					{$locale.041}{$comments[id].comment_datestamp|date_format:"longdate"}
				</span>
			</td>
		</tr>
		<tr>
			<td class='tbl1'>
				{$comments[id].comment_message}
			</td>
		</tr>
		<tr>
			<td align='right' class='tbl2'>
				{buttonlink name=$locale.411 link=$smarty.const.FUSION_SELF|cat:$aidlink|cat:"&amp;step=edit&amp;comment_id="|cat:$comments[id].comment_id|cat:"&amp;ctype="|cat:$ctype|cat:"&amp;cid="|cat:$cid}
				{buttonlink name=$locale.412 link="if (DeleteItem()) window.location=\""|cat:$smarty.const.FUSION_SELF|cat:$aidlink|cat:"&amp;step=delete&amp;comment_id="|cat:$comments[id].comment_id|cat:"&amp;ctype="|cat:$ctype|cat:"&amp;cid="|cat:$cid|cat:"\";" script="yes"}
				{buttonlink name=$locale.413 link=$smarty.const.ADMIN|cat:"blacklist.php"|cat:$aidlink|cat:"&amp;ip="|cat:$comments[id].comment_ip|cat:"&amp;reason=470"}
			</td>
		</tr>
	</table>
	<table align='center' cellpadding='0' cellspacing='0'>
		<tr>
			<td style='height:5px;'>
			</td>
		</tr>
	</table>
{sectionelse}
	<center>
		<br />
		{$locale.415}
		<br /><br />
	</center>
{/section}
{include file="_closetable.tpl"}
<script type="text/javascript">
function DeleteItem() {ldelim}
	return confirm("{$locale.414}");
{rdelim}
</script>
{***************************************************************************}
{* End of template                                                         *}
{***************************************************************************}
