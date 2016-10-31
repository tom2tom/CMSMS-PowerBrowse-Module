{if !empty($message)}<h3>{$message}</h3><br />{/if}
<h2 style="margin-left:5%;">{$browser_title}</h2>
{if !empty($rows)}
 {if $hasnav}
  <div class="pbr_browsenav pageinput">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}&nbsp;({$pageof})&nbsp;&nbsp;{$rowchanger}</div>
 {/if}
  <div class="pageinput pbr_overflow">
   <table id="submissions" class="pagetable leftwards">
    <thead><tr>
{foreach from=$colnames key=fcol item=fname}
     <th class="{ldelim}sss:{if $colsorts[$fcol]}'text'{else}false{/if}{rdelim}">{$fname}</th>
{/foreach}
    </tr></thead>
    <tbody>
{foreach from=$rows item=resp}{cycle values='row1,row2' assign='rowclass'}
     <tr class="{$rowclass}">
{foreach from=$resp item=value}<td>{$value|escape}</td>{/foreach}
     </tr>
{/foreach}
    </tbody>
   </table>
  </div>
{if $hasnav}<div class="pbr_browsenav pageinput">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}</div>{/if}
{else}
 <p class="pageinput">{$norecords}</p>
{/if}
