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
{* report template include: generate an overview of users per country      *}
{*                                                                         *}
{***************************************************************************}
{if $action == "report"}
	<table width='100%' class='tbl-border' cellspacing='1'>
		<tr>
			<td align='left' class='tbl2' colspan='2'>
				<b>{$locale.rpt510}</b>
			</td>
			<td align='center' class='tbl2'>
				<b>{$locale.rpt508}</b>
			</td>
		</tr>
		{section name=id loop=$reportvars.output}
		<tr>
			<td align='right' width='1' class='{cycle values="tbl1,tbl2" advance=false}'>
				{$reportvars.output[id]._rownr}
			</td>
			<td align='left' class='{cycle values="tbl1,tbl2" advance=false}'>
				{if $reportvars.output[id].max_date == 0}
					{$locale.rpt511}
				{else}
					{$reportvars.output[id].year}, {$reportvars.output[id].monthname}
				{/if}
			</td>
			<td align='center' class='{cycle values="tbl1,tbl2"}'>
				{$reportvars.output[id].count}
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td align='center' class='tbl1' colspan='2'>
				<b>{$locale.rpt951}</b>
			</td>
		</tr>
		{/section}
	</table>
	{if $rows > $settings.numofthreads}
		<br />
		{makepagenav start=$rowstart count=$settings.numofthreads total=$rows range=3 link=$pagenav_url}
	{/if}
{else}
	<table width='100%'>
		<tr>
			<td align='left' colspan='2'>
				{$locale.rpt501}
				<select name='top' class='textbox'>
					{section name=cnt loop=55 start=5 step=5}
					<option value='{$smarty.section.cnt.index}'>{$locale.rpt502} {$smarty.section.cnt.index}</option>
					{/section}
					<option value='0'>{$locale.rpt503}</option>
				</select>
				{$locale.rpt504}
				<select name='sortorder' class='textbox'>
					<option value='0'>{$locale.rpt505}</option>
					<option value='1'>{$locale.rpt506}</option>
				</select>
			</td>
		</tr>
	</table>
{/if}
{***************************************************************************}
{* End of template                                                         *}
{***************************************************************************}
