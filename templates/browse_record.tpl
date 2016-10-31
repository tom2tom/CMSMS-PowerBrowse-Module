{if !empty($inner_nav)}<div class="pbr_innernav">{$inner_nav}</div><br />{/if}
<div class="pageinput">
{if !empty($message)}<br /><h3>{$message}</h3><br />{/if}
 <h2>{$title_browser}</h2>
 <br />
{$start_form}
{$hidden}
 <div class="pbr_overflow">
{foreach from=$content item=field}<p><strong>{$field[0]}</strong><br />{$field[1]}</p>{/foreach}
 </div>
{if isset($submit)}{$submit} {/if}{$cancel}
{$end_form}
</div>
