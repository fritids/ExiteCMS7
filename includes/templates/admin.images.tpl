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
{* Template for the admin content module 'images'                          *}
{*                                                                         *}
{***************************************************************************}
{include file="_opentable.tpl" name=$_name title=$locale.420 state=$_state style=$_style}
<form name='uploadform' method='post' action='{$smarty.const.FUSION_SELF}{$aidlink}&amp;ifolder={$ifolder}' enctype='multipart/form-data'>
	<table align='center' cellpadding='0' cellspacing='0' width='350'>
		<tr>
			<td width='80' class='tbl'>
				{$locale.421}
			</td>
			<td class='tbl'>
				<input type='file' name='myfile' class='textbox' style='width:250px;' />
			</td>
		</tr>
		<tr>
			<td align='center' colspan='2' class='tbl'>
				<input type='submit' name='uploadimage' value='{$locale.420}' class='button' style='width:100px;' />
			</td>
		</tr>
	</table>
</form>
{include file="_closetable.tpl"}
{if $view|default:"" != ""}
	{include file="_opentable.tpl" name=$_name title=$locale.440 state=$_state style=$_style}
	<center>
		<br />
		{if $view_image|default:"" != ""}
			<img src='{$view_image}' alt='{$view}' />
		{else}
			{$locale.441}
		{/if}
		<br /><br >
		{buttonlink name=$locale.462 link=$smarty.const.FUSION_SELF|cat:$aidlink|cat:"&amp;ifolder="|cat:$ifolder|cat:"&amp;del="|cat:$view}&nbsp;
		{buttonlink name=$locale.465 link=$smarty.const.FUSION_SELF|cat:$aidlink|cat:"&amp;ifolder="|cat:$ifolder}
		<br /><br />
	</center>
	{include file="_closetable.tpl"}
{else}
	{include file="_opentable.tpl" name=$_name title=$locale.460 state=$_state style=$_style}
	<table align='center' cellpadding='0' cellspacing='1' width='450' class='tbl-border'>
		<tr>
			<td align='center' colspan='2' class='tbl2'>
				{section name=id loop=$image_cats}
				<span style='font-weight:{if $image_cats[id].selected}bold{else}normal{/if}'><a href='{$smarty.const.FUSION_SELF}{$aidlink}&amp;ifolder={$image_cats[id].folder}'>{$image_cats[id].name}</a></span>{if !$smarty.section.id.last} |{/if}
				{/section}
			</td>
		</tr>
		{foreach from=$image_list item=image name=image_list}
		<tr>
			<td class='{cycle values='tbl1,tbl2' advance=no}'>
				{$image}
			</td>
			<td align='center' width='50' class='{cycle values='tbl1,tbl2'}' style='white-space:nowrap'>
				<a href='{$smarty.const.FUSION_SELF}{$aidlink}&amp;ifolder={$ifolder}&amp;view={$image}'><img src='{$smarty.const.THEME}images/image_view.gif' alt='{$locale.461}' title='{$locale.461}' /></a>&nbsp;
				<a href='{$smarty.const.FUSION_SELF}{$aidlink}&amp;ifolder={$ifolder}&amp;del={$image}'><img src='{$smarty.const.THEME}images/image_delete.gif' alt='{$locale.462}' title='{$locale.462}' /></a>
			</td>
		</tr>
		{if $smarty.foreach.image_list.last && $settings.tinymce_enabled}
			<tr>
				<td align='center' colspan='2' class='{cycle values='tbl1,tbl2'}'>
					{buttonlink name=$locale.464 link=$smarty.const.FUSION_SELF|cat:$aidlink|cat:"&amp;ifolder="|cat:$ifolder|cat:"&amp;action=update"}
				</td>
			</tr>
		{/if}
		{foreachelse}
		<tr>
			<td align='center' class='tbl1'>
				{$locale.463}
			</td>
		</tr>
		{/foreach}
	</table>
	{include file="_closetable.tpl"}
{/if}
{***************************************************************************}
{* End of template                                                         *}
{***************************************************************************}
