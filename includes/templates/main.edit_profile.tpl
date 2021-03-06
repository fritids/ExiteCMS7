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
{* This template generates the PLi-Fusion panel: edit_profile              *}
{*                                                                         *}
{***************************************************************************}
{include file="_opentable.tpl" name=$_name title=$locale.440 state=$_state style=$_style}
<form name='edit_profile_form' method='post' action='{$smarty.const.FUSION_SELF}{if $is_admin}{$aidlink}&amp;user_id={$this_userdata.user_id}{/if}' enctype='multipart/form-data'>
	<table align='center' cellpadding='0' cellspacing='0'>
		{if $update_profile}
			{if $error|default:"" == ""}
			<tr>
				<td align='center' colspan='2' class='tbl'>
					<b>{$locale.441}</b>
					<br /><br />
				</td>
			</tr>
			{else}
			<tr>
				<td align='center' colspan='2' class='tbl'>
					<b>{$locale.442}
					<br />
					{$error}</b>
					<br />
				</td>
			</tr>
			{/if}
		{/if}
		{if $auth_userpass}
		<tr>
			<td class='tbl'>
				{$locale.u001}<span style='color:#ff0000'>*</span>
			</td>
			<td class='tbl'>
				<input type='text' name='user_name' value='{$this_userdata.user_name}' maxlength='30' class='textbox' style='width:200px;' />
			</td>
		</tr>
		{/if}
		<tr>
			<td class='tbl'>
				{$locale.u901}<span style='color:#ff0000'>*</span>
			</td>
			<td class='tbl'>
				<input type='text' name='user_fullname' value='{$this_userdata.user_fullname}' maxlength='50' class='textbox' style='width:200px;' />
			</td>
		</tr>
		{if $auth_userpass}
		<tr>
			<td class='tbl'>
				{$locale.u003}
			</td>
			<td class='tbl'>
				<input type='password' name='user_newpassword' maxlength='20' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u004}</td>
			<td class='tbl'>
				<input type='password' name='user_newpassword2' maxlength='20' class='textbox' style='width:200px;' />
			</td>
		</tr>
		{/if}
		{if $auth_openid}
		<tr>
			<td class='tbl'>
				{$locale.u066}</td>
			<td class='tbl'>
				<input type='text' name='user_openid_url' value='{$this_userdata.user_openid_url}' maxlength='200' class='textbox' style='width:275px;background: url({$smarty.const.IMAGES}openid_small_logo.gif) no-repeat; padding-left: 18px;' />
			</td>
		</tr>
		{/if}
		<tr>
			<td class='tbl'>
				{$locale.u005}<span style='color:#ff0000'>*</span>
			</td>
			<td class='tbl'>
				<input type='text' name='user_email' value='{$this_userdata.user_email}' maxlength='100' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u006}
			</td>
			<td class='tbl'>
				<input type='radio' name='user_hide_email' value='1' {if $this_userdata.user_hide_email == "1"}checked="checked"{/if} />{$locale.u007}
				<input type='radio' name='user_hide_email' value='0' {if $this_userdata.user_hide_email == "0"}checked="checked"{/if} />{$locale.u008}
			</td>
		</tr>
		{if $settings.hoteditor_enabled == 1}
		<tr>
			<td class='tbl'>
				{$locale.u067}
			</td>
			<td class='tbl'>
				<input type='radio' name='user_hoteditor' value='1' {if $this_userdata.user_hoteditor == "1"}checked="checked"{/if} />{$locale.u007}
				<input type='radio' name='user_hoteditor' value='0' {if $this_userdata.user_hoteditor == "0"}checked="checked"{/if} />{$locale.u008}
			</td>
		</tr>
		{/if}
		<tr>
			<td class='tbl'>
				{$locale.u026}
			</td>
			<td class='tbl'>
				<input type='radio' name='user_newsletters' value='1' {if $this_userdata.user_newsletters == "1"}checked="checked"{/if} />{$locale.u037}
				<input type='radio' name='user_newsletters' value='2' {if $this_userdata.user_newsletters == "2"}checked="checked"{/if} />{$locale.u038}
				<input type='radio' name='user_newsletters' value='0' {if $this_userdata.user_newsletters == "0"}checked="checked"{/if} />{$locale.u039}
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u024}
			</td>
			<td class='tbl'>
				<input type='radio' name='user_forum_fullscreen' value='1' {if $this_userdata.user_forum_fullscreen == "1"}checked="checked"{/if} />{$locale.u007}
				<input type='radio' name='user_forum_fullscreen' value='0' {if $this_userdata.user_forum_fullscreen == "0"}checked="checked"{/if} />{$locale.u008}
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u035}
			</td>
			<td class='tbl'>
				<input type='radio' name='user_posts_unread' value='1' {if $this_userdata.user_posts_unread == "1"}checked="checked"{/if} />{$locale.u007}
				<input type='radio' name='user_posts_unread' value='0' {if $this_userdata.user_posts_unread == "0"}checked="checked"{/if} />{$locale.u008}
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u036}
			</td>
			<td class='tbl'>
				<input type='radio' name='user_posts_track' value='1' {if $this_userdata.user_posts_track == "1"}checked="checked"{/if} />{$locale.u007}
				<input type='radio' name='user_posts_track' value='0' {if $this_userdata.user_posts_track == "0"}checked="checked"{/if} />{$locale.u008}
				{buttonlink name=$locale.u068 link=$smarty.const.BASEDIR|cat:"forum/tracking.php"}
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u069}
			</td>
			<td class='tbl'>
				<select name='user_numofthreads' class='textbox'>
				<option value='0' {if $this_userdata.user_numofthreads == 0}selected='selected'{/if}>{$locale.u070}</option>
				{section name=num start=5 loop=101 step=5}
				<option value='{$smarty.section.num.index}' {if $smarty.section.num.index == $this_userdata.user_numofthreads}selected='selected'{/if}>{$smarty.section.num.index}</option>
				{/section}
				</select>
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u009}
			</td>
			<td class='tbl'>
				<input type='text' name='user_location' value='{$this_userdata.user_location}' maxlength='50' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
		<td class='tbl'>
			{$locale.u010}
		</td>
			<td class='tbl'>
				{html_select_date prefix='user_' time=$this_userdata.user_birthdate start_year="1900" end_year="-1" day_empty="--" month_empty="--" year_empty="--" all_extra="class='textbox'"}
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u062}
			</td>
			<td class='tbl'>
				<select name='user_gender' class='textbox' style='width:200px;'>
						<option value='' {if $this_userdata.user_gender == ''}selected="selected"{/if}>{$locale.u065}</option>
						<option value='F' {if $this_userdata.user_gender == 'F'}selected="selected"{/if}>{$locale.u064}</option>
						<option value='M' {if $this_userdata.user_gender == 'M'}selected="selected"{/if}>{$locale.u063}</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u021}
			</td>
			<td class='tbl'>
				<input type='text' name='user_aim' value='{$this_userdata.user_aim}' maxlength='16' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u011}
			</td>
			<td class='tbl'>
				<input type='text' name='user_icq' value='{$this_userdata.user_icq}' maxlength='15' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u012}
			</td>
			<td class='tbl'>
				<input type='text' name='user_msn' value='{$this_userdata.user_msn}' maxlength='100' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u013}
			</td>
			<td class='tbl'>
				<input type='text' name='user_yahoo' value='{$this_userdata.user_yahoo}' maxlength='100' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u014}
			</td>
			<td class='tbl'>
				<input type='text' name='user_web' value='{$this_userdata.user_web}' maxlength='100' class='textbox' style='width:200px;' />
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u028}
			</td>
			<td class='tbl'>
				<select name='user_locale' class='textbox' style='width:200px;'>
					{section name=locales loop=$locales}
						<option value='{$locales[locales].locale_code}' {if $locales[locales].selected}selected="selected"{/if}>{$locales[locales].locale_name}</option>
					{/section}
				</select>
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u015}
			</td>
			<td class='tbl'>
				<select name='user_theme' class='textbox' style='width:200px;'>
					{foreach from=$theme_files item=theme}
						<option{if $this_userdata.user_theme ==  $theme} selected="selected"{/if}>{$theme}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td class='tbl'>
				{$locale.u016}
			</td>
			<td class='tbl'>
				<select name='user_offset' class='textbox' style='width:75px;'>
					{section name=offset max=24 loop=25 step=-1}
						<option{if $this_userdata.user_offset == $smarty.section.offset.index/-2} selected="selected"{/if}>{$smarty.section.offset.index/-2}</option>
					{/section}
					{section name=offset start=0 loop=25 step=1}
						<option{if $this_userdata.user_offset == $smarty.section.offset.index/2} selected="selected"{/if}>+{$smarty.section.offset.index/2}</option>
					{/section}
				</select>
				&nbsp;
				<input type='button' value='{$locale.u027}' class='button' onclick="autotimezone();return false;" />
				&nbsp;
				{$timezone}
			</td>
		</tr>
		{if $this_userdata.user_avatar|default:"" == ""}
		<tr>
			<td class='tbl'>
				{$locale.u017}
			</td>
			<td class='tbl'>
				<input type='file' name='user_avatar' class='textbox' style='width:200px;' />
				<br />
				<span class='small2'>{$locale.u018}</span>
				<br />
				<span class='small2'>{ssprintf format=$locale.u022 var1=$avatar.size var2=$avatar.x var3=$avatar.y}</span>
			</td>
		</tr>
		{/if}
		<tr>
			<td class='tbl' valign='top'>
				{$locale.u020}
			</td>
			<td class='tbl'>
				{if $settings.hoteditor_enabled == 0 || $userdata.user_hoteditor == 0}
					<textarea name='user_sig' rows='5' cols='80' class='textbox' style='width:295px'>{$this_userdata.user_sig}</textarea><br />
					<input type='button' value='b' class='button' style='font-weight:bold;width:25px;' onclick="addText('user_sig', '[b]', '[/b]');" />
					<input type='button' value='i' class='button' style='font-style:italic;width:25px;' onclick="addText('user_sig', '[i]', '[/i]');" />
					<input type='button' value='u' class='button' style='text-decoration:underline;width:25px;' onclick="addText('user_sig', '[u]', '[/u]');" />
					<input type='button' value='url' class='button' style='width:30px;' onclick="addText('user_sig', '[url]', '[/url]');" />
					<input type='button' value='mail' class='button' style='width:35px;' onclick="addText('user_sig', '[mail]', '[/mail]');" />
					<input type='button' value='img' class='button' style='width:30px;' onclick="addText('user_sig', '[img]', '[/img]');" />
					<input type='button' value='center' class='button' style='width:45px;' onclick="addText('user_sig', '[center]', '[/center]');" />
					<input type='button' value='small' class='button' style='width:40px;' onclick="addText('user_sig', '[small]', '[/small]');" />
				{else}
					<script language="javascript" type="text/javascript">
						// non-standard toolbars for this editor instance
						var toolbar1 ="SPACE,btFont_Name,btFont_Size,btFont_Color,btHighlight";
						var toolbar2 ="SPACE,btRemove_Format,SPACE,btBold,btItalic,btUnderline,SPACE,btAlign_Left,btCenter,btAlign_Right,SPACE,btStrikethrough,btSubscript,btSuperscript,btHorizontal";
						var toolbar3 ="SPACE,btHyperlink,btHyperlink_Email,btInsert_Image,btEmotions";

						var textarea_toolbar1 ="SPACE,btFont_Name,btFont_Size,btFont_Color,btHighlight";
						var textarea_toolbar2 ="SPACE,btRemove_Format,SPACE,btBold,btItalic,btUnderline,SPACE,btAlign_Left,btCenter,btAlign_Right,SPACE,btStrikethrough,btSubscript,btSuperscript,btHorizontal";
						var textarea_toolbar3 ="SPACE,btHyperlink,btHyperlink_Email,btInsert_Image,btEmotions";
					</script>
					{include file="_bbcode_editor.tpl" name="user_sig" id="user_sig" author="" message=$this_userdata.user_sig width="250px" height="200px"}
				{/if}
			</td>
		</tr>
		<tr>
			<td align='center' colspan='2' class='tbl'>
				<br />
				{if $this_userdata.user_avatar|default:"" != ""}
					{$locale.u017}
					<br />
					<img src='{$smarty.const.IMAGES_AV}{$this_userdata.user_avatar}' alt='{$locale.u017}' />
					<br />
					<input type='checkbox' name='del_avatar' value='y' /> {$locale.u019}
					<input type='hidden' name='user_avatar' value='{$this_userdata.user_avatar}' />
					<br /><br />
				{/if}
				<input type='hidden' name='user_hash' value='{$this_userdata.user_password}' />
				{if $settings.hoteditor_enabled == 0 || $userdata.user_hoteditor == 0}
					<input type='submit' name='update_profile' value='{$locale.460}' class='button' />
				{else}
					<input type='submit' name='update_profile' value='{$locale.460}' class='button' onclick='javascript:get_hoteditor_data("user_sig");' />
				{/if}
			</td>
		</tr>
	</table>
</form>
{literal}<script type='text/javascript'>
//
// calculate the offset between browser and server time
//
function autotimezone() {
	var now = new Date();
	var serveroffset = {/literal}{$serveroffset|default:0}{literal};
	var offset = now.getTimezoneOffset() / -60 - serveroffset;
	hours = parseInt(offset);
	if (hours < 0) var minutes = (offset - hours) * -60; else var minutes = (offset - hours) * 60;
	if (minutes != 0) minutes = ':' + minutes; else minutes = '';
	offset = hours + minutes;
	if (hours >= 0) offset = "+" + offset;
	//
	// and preselect the correct time offset value
	//
	var dropdown = document.forms['edit_profile_form'].elements['user_offset'];
	for (var i=0; i < dropdown.options.length; i++) {
		if (dropdown.options[i].value == offset) {
			dropdown.selectedIndex = i;
			break;
		}
	}
}
function ValidateForm(frm) {
	if (frm.username.value=="") {
		alert("{/literal}{$locale.550}{literal}");
		return false;
	}
	if (frm.password1.value=="") {
		alert("{/literal}{$locale.551}{literal}");
		return false;
	}
	if (frm.email.value=="") {
		alert("{/literal}{$locale.552}{literal}");
		return false;
	}
}
</script>{/literal}
{include file="_closetable.tpl"}
{***************************************************************************}
{* End of template                                                         *}
{***************************************************************************}
