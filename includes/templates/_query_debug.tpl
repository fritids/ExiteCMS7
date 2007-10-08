{***************************************************************************}
{*                                                                         *}
{* ExiteCMS template: _query_debug.tpl                                     *}
{*                                                                         *}
{***************************************************************************}
{*                                                                         *}
{* Author: WanWizard <wanwizard@gmail.com>                                 *}
{*                                                                         *}
{* Revision History:                                                       *}
{* 2007-08-03 - WW - Initial version                                       *}
{*                                                                         *}
{***************************************************************************}
{*                                                                         *}
{* This template is used to dump the recorded list of executed DB queries  *}
{*                                                                         *}
{***************************************************************************}
<br />
{include file="_opentable.tpl" name=$_name title="DEBUG PANEL: List of logged database queries" state=$_state style=$_style}
<table width='100%' cellspacing='1' cellpadding='0' class='tbl-border'>
	<tr>
		<td width='1%' class='{cycle values='tbl1,tbl2' advance=no}'>
			<b>Seq.</b>
		</td>
		<td width='1%' align='center' class='{cycle values='tbl1,tbl2' advance=no}'>
			<b>Time</b>
		</td>
		<td class='{cycle values='tbl1,tbl2'}'>
			<b>Query Statement</b>
		</td>
	</tr>
	{section name=id loop=$queries}
	<tr>
		<td width='1%' class='{cycle values='tbl1,tbl2' advance=no}'>
			{$smarty.section.id.iteration}
		</td>
		<td width='1%' class='{cycle values='tbl1,tbl2' advance=no}'>
			{if $queries[id].1 > 2500}<font color='#FF0000'><b>
			{elseif $queries[id].1 > 500}<font color='#FF6600'><b>
			{else}<font color='#009900'>
			{/if}				
			{$queries[id].1|string_format:"%01.3f"}ms
			{if $queries[id].1 > 500}</b>{/if}
			</font>
		</td>
		<td class='{cycle values='tbl1,tbl2'}'>
			{$queries[id].0}
		</td>
	</tr>
	{/section}
</table>
{include file="_closetable.tpl"}
{***************************************************************************}
{* End of template                                                         *}
{***************************************************************************}