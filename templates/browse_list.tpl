<link rel="stylesheet" type="text/css" href="{$cssurl}" />
{if !empty($inner_nav)}<div class="pbr_innernav">{$inner_nav}</div><br />{/if}
{if !empty($message)}<h3>{$message}</h3><br />{/if}
<h2 style="margin-left:5%;">{$browser_title}</h2>
{if !empty($rows)}
	{if $hasnav}
		<div class="pbr_browsenav">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}&nbsp;({$pageof})&nbsp;&nbsp;{$rowchanger}</div>
	{/if}
	{$start_form}
		<div class="pageinput pbr_overflow">
			 <table id="submissions" class="pagetable leftwards">
			  <thead><tr>
				<th class="{ldelim}sss:'isoDate'{rdelim}">{$title_submit_when}</th>
{foreach from=$colnames key=fcol item=fname}
				<th class="{ldelim}sss:{if $colsorts[$fcol]}'text'{else}false{/if}{rdelim}">{$fname}</th>
			{/foreach}
				<th class="pageicon {ldelim}sss:false{rdelim}"></th>
	  {if $pmod}<th class="pageicon {ldelim}sss:false{rdelim}"></th>
				<th class="pageicon {ldelim}sss:false{rdelim}"></th>{/if}
				<th class="pageicon {ldelim}sss:false{rdelim}"></th>
				<th class="checkbox {ldelim}sss:false{rdelim}" style="width:20px;">{$header_checkbox}</th>
			  </tr></thead>
			  <tbody>
{foreach from=$rows item=resp}{cycle values='row1,row2' assign='rowclass'}
				<tr class="{$rowclass}">
				 <td>{$resp->submitted}</td>
{foreach from=$resp->fields item=value}<td>{$value|escape}</td>{/foreach}
				 <td>{$resp->view}</td>
		{if $pmod}<td>{$resp->edit}</td>{/if}
				 <td>{$resp->export}</td>
		{if $pmod}<td>{$resp->delete}</td>{/if}
				 <td>{$resp->selected}</td>
				</tr>
{/foreach}
			  </tbody>
			 </table>
		</div>
		{if $hasnav}<div class="pbr_browsenav">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}</div>{/if}
		<div class="pageinput" style="margin-top:0; float:right; text-align:right">{$export}{if $pmod}&nbsp;{$delete}{/if}</div>
	{$end_form}
{else}
	<p class="pageinput">{$norecords}</p>
{/if}

{if $pmod}<p class="pageinput">{$iconlinkadd}&nbsp;{$textlinkadd}</p>{/if}
<div style="clear:right;"></div>

<script type="text/javascript" src="{$modurl}/include/jquery.metadata.min.js"></script>
<script type="text/javascript" src="{$modurl}/include/jquery.SSsort.min.js"></script>
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
