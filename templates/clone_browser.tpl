{if !empty($message)}<p class="pagemessage">{$message}</p>{/if}
{if !empty($inner_nav)}<div class="pbr_innernav">{$inner_nav}</div>{/if}
{$start_form}{$hidden}
 <div class="pbr_overflow">
  <p class="pagetext">{$title_browser_oldname}:</p>
  <p class="pageinput">{$browser_oldname}</p>
  <p class="pagetext">{$title_form_name}:</p>
  <p class="pageinput">{$form_name}</p>
  <p class="pagetext">{$title_browser_name}:</p>
  <p class="pageinput">{$input_browser_name}</p>
<br />
  <div class="pageinput">{$save} {$cancel}</div>
 </div>
{$end_form}
