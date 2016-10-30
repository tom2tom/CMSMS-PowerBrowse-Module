{if !empty($message)}<p class="pagemessage">{$message}</p>{/if}
{if !empty($inner_nav)}<div class="pbr_innernav">{$inner_nav}</div>{/if}
{$start_form}{$hidden}
{$tabs_start}
{$maintab_start}
 <div class="pbr_overflow">
  <p class="pagetext" style="margin-top:0;">{$title_browser_name}:</p>
  <p class="pageinput">{$input_browser_name}</p>
  <p class="pagetext">{$title_form_name}:</p>
  <p class="pageinput">{$form_name}</p>
{if isset($title_browser_owner)}<p class="pagetext">{$title_browser_owner}:</p>
  <p class="pageinput">{$input_browser_owner}</p>{/if}
 </div>
{$tab_end}{$listtab_start}
{if ($rc > 0)}
  <p class="pagetext" style="margin-top:0;">{$title_pagerows}:</p>
  <p class="pageinput">{$input_pagerows}<br />{$help_pagerows}</p>
  <p class="pagetext">{$title_data}:</p>
 <div class="pageinput pbr_overflow">
 <table id="listfields" class="pagetable leftwards{if ($rc > 1)} drag{/if}">
 <thead><tr>
  <th>{$title_name}</th>
  <th style="text-align:center;">{$title_admdisplay}{if !empty($select_all1)}<br />{$select_all1}{/if}</th>
  <th style="text-align:center;">{$title_frntdisplay}{if !empty($select_all2)}<br />{$select_all2}{/if}</th>
  <th style="text-align:center;">{$title_sort}{if !empty($select_all3)}<br />{$select_all3}{/if}</th>
{if ($rc > 1)}<th class="updown">{$title_move}</th>{/if}
 </tr></thead>
 <tbody>
 {foreach from=$fields item=entry}{cycle name=admin values='row1,row2' assign=rowclass}
 <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
  <td>{$entry->order}{$entry->name}</td>
  <td style="text-align:center;">{$entry->display}</td>
  <td style="text-align:center;">{$entry->front}</td>
  <td style="text-align:center;">{$entry->sort}</td>
  {if ($rc > 1)}<td class="updown">{$entry->down}{$entry->up}</td>{/if}
 </tr>
 {/foreach}
 </tbody>
 </table>
{if ($rc > 1)}<p>{$help_order}</p>
 <div class="dndhelp">{$help_dnd}</div>{/if}
 </div>
{else}
 <p class="pageinput">{$nofields}</p>
{/if}
{$tab_end}
{$tabs_end}
<p class="pageinput" style="margin-top:1em;">{$save} {$cancel} {$apply}</p>
{$end_form}
