{if !empty($message)}<p>{$message}</p>{/if}
{$tabs_header}
{$start_browsers_tab}
{$start_browsersform}
{if $browser_count > 0}
<div class="pageinput pbr_overflow">
 <table class="pagetable leftwards">
  <thead><tr>
	<th>{$title_browser_name}</th>
{if $pdev}<th>{$title_related_form}</th>{/if}
	<th>{$title_records}</th>
	<th class="pageicon"></th>
	<th class="pageicon"></th>
{if $pmod}
	<th class="pageicon"></th>
	<th class="pageicon"></th>
	<th class="pageicon"></th>
{/if}
 	<th class="checkbox">{$selectall_browsers}</th>
  </tr></thead>
  <tbody>
{foreach from=$browsers item=entry}{cycle values='row1,row2' assign=rowclass}
	<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
	 <td>{$entry->name}</td>
{if $pdev}<td>{$entry->form_name}</td>{/if}
	 <td style="text-align:center;">{$entry->recordcount}</td>
	 <td>{$entry->adminlink}</td>
	 <td>{$entry->exportlink}</td>
{if $pmod}
	 <td>{$entry->editlink}</td>
	 <td>{$entry->clonelink}</td>
	 <td>{$entry->deletelink}</td>
{/if}
	 <td class="checkbox">{$entry->selected}</td>
	</tr>
{/foreach}
  </tbody>
 </table>
</div>
{else}
<p class="pageinput">{$nobrowsers}</p>
{/if}
<br />
<div class="pageinput" style="text-align:justify">
{if ($pconfig || $pmod)}{$addlink}&nbsp;{$addbrowser}&nbsp;&nbsp;{/if}
{if $browser_count > 0}&nbsp;{$exportbtn}
	{if $pmod}&nbsp;{$clonebtn}&nbsp;{$deletebtn}{/if}
{/if}
{if !empty($importbtn)}&nbsp;{$importbtn}{/if}
</div>
{$end_form}
{$end_tab}

{if $pconfig}
{$start_settings_tab}
{$start_settingsform}
 <div class="pbr_overflow">
{foreach from=$configs item=entry}
 <p class="pagetext">{$entry->title}:</p>
 <div class="pageinput">{$entry->input}{if !empty($entry->help)}<br />{$entry->help}{/if}</div>
{/foreach}
 <div class="pageinput" style="margin-top:20px;">{$save}&nbsp;{$cancel}</div>
</div>
{$end_form}
{$end_tab}
{/if}
{$tabs_footer}
