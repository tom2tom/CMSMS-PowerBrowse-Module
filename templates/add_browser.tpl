{if !empty($message)}<p class="pagemessage">{$message}</p>{/if}
{if !empty($inner_nav)}<div class="pbr_innernav">{$inner_nav}</div>{/if}
{$start_form}{$hidden}
 <div class="pbr_overflow">
  <p class="pagetext">{$title_form_name}:</p>
  <p class="pageinput">{$input_form_name}</p>
  <p class="pagetext">{$title_browser_name}:</p>
  <p class="pageinput">{$input_browser_name}</p>
 </div>
  <p class="pageinput" style="margin-top:10px;">{$save} {$cancel}</p>
{$end_form}
