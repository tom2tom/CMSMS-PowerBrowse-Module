<?php

$lang['accessdenied']='Access denied. Please check your permissions.';
$lang['add']='Add';
$lang['added']='Added';
$lang['admindescription']='Administer, edit, configure browsers';
$lang['all']='All';
$lang['apply']='Apply';

$lang['back']='Back to list';
$lang['browser1']='Browser %s';
$lang['browser2']='Browser %s %s';
$lang['browser_deleted']='The browser has been deleted';

$lang['cancel']='Cancel';
$lang['clone']='Clone';
$lang['close']='Close';
$lang['confirm']='Are you sure?';
$lang['confirm_delete_browser']='Are you sure you want to delete %s';
$lang['confirm_delete_record']='Are you sure you want to delete this submission?';
$lang['confirm_delete_sel']='Are you sure you want to delete the selected records?';
$lang['confirm_uninstall']='Are you sure you want to uninstall PowerBrowse?';

$lang['delete']='Delete';
$lang['deleted2']='Deleted %s.';
$lang['do_not_display']='Not Displayed';
$lang['down']='Move down';

$lang['edit']='Edit';
$lang['error']='Error!';
$lang['error_data']='Cannot process stored data';
$lang['error_database']='Cannot find requested data';
$lang['error_export']='A problem occurred during the export process.'; //how vague is that !!
$lang['error_failed']='The requested operation failed to complete';
$lang['error_missing']='Cannot find it';
$lang['error_module']='The PowerForms module is missing. Please advise your site administrator.';
$lang['error_noform']='Cannot find a requested form. Please advise your site administrator.';
$lang['error_system']='Something is not working correctly. Please advise your site administrator.';
$lang['error_zip']='Zip file creation failed';
$lang['export']='Export';

$lang['field_label'] = 'Store results for later browsing';
$lang['first']='first';
$lang['friendlyname']='Forms Data Browser';

//$lang['help_browser_css_class']='Optional name of class, or space-separated series of class names, applied to list views';
$lang['help_dnd']='You can change the order by dragging any row, or double-click on any number of rows prior to dragging them all.';
$lang['help_export_file']='Progressively create each .csv file in the general or specific <em>uploads</em> directory, instead of processing the export in memory. This may be wise if there is a lot of data to export. The downside is that someone needs to get that file and (usually) then delete it.';
$lang['help_field_draggable']='Display order can be changed by dragging row(s).';
$lang['help_list_cssfile']='A .css file in the general or specific <em>uploads</em> directory. Module help provides details about the contents. If left blank, default styles will be used.';
$lang['help_oldmodule_data']='Work directly with data recorded by those modules, instead of PowerForms';
$lang['help_onchange_notices']='If a form has disposition(s) that send email or other notice, such notice will not be sent after a record is edited via the PowerBrowse admin, if this option is de-selected.';
$lang['help_order']='Row-order here corresponds to column-order for listed data.';
$lang['help_owned_forms']='Enable blocking of form-data access by any non-administrator other than a specified user';
$lang['help_pagerows']='This is the minimum length of displayed pages. The length can be increased, while browsing.';
$lang['help_strip_on_export']='Remove all HTML tags from records when exported to .csv';
$lang['help_uploads_path']='A filesystem path relative to website-host <em>uploads</em> directory. No leading or trailing path-separator, and any intermediate path-separator must be host-system-specific e.g. \'\\\' on Windows. If left blank, the default directory will be used.';

$lang['import_browsers']='Import FormBrowsers';
$lang['inspect']='Inspect';

$lang['last']='last';

$lang['message_records_deleted']='%d submission(s) deleted';
//$lang['message_records_exported']='%d submission(s) exported';
$lang['moddescription']='PowerBrowse enables review of submitted form data.';

$lang['next']='next';
$lang['nobrowsers']='No browser is registered';
$lang['nofields']='The form has no user-input fields';
$lang['noforms']='No PowerForms form is browsable';
$lang['norecords']='No data are recorded for this browser';
$lang['none']='None';

$lang['owner']='Owner';

$lang['pageof']='showing page %s of %s';
$lang['pagerows']='rows-per-page';
$lang['perm_browsers']='Modify PowerForm Browsers';
$lang['perm_data']='Modify Recorded PowerForm Data';
$lang['perm_see']='View/export Recorded PowerForm Data';
$lang['postinstall']='PowerBrowse module has been installed. Remember to apply relevant permissions.';
$lang['postuninstall']='PowerBrowse module has been uninstalled.';
$lang['prefs_updated']='Preferences updated.';
$lang['previous']='previous';

$lang['save']='Submit';
$lang['save_and_continue']='Save and continue editing';
$lang['saved']='saved';
$lang['select_form']='Select a form';
$lang['submit']='Submit';

$lang['tab_list']='Display';
$lang['tab_main']='Browser';
$lang['tab_settings']='Settings';
$lang['tip_clone_selected_browsers']='clone selected browsers';
$lang['tip_delete_selected_browsers']='delete selected browsers';
$lang['tip_delete_selected_records']='delete selected records';
$lang['tip_export_selected_browsers']='export recorded data for all selected browsers';
$lang['tip_export_selected_records']='export selected records';
$lang['tip_import_browsers']='import data from FormBrowser module'; 
$lang['title_add_browser']='Add new browser';
$lang['title_add_record']='Add a record';
$lang['title_browser_export']='Export';
$lang['title_browser_name']='Name';
$lang['title_browser_oldname']='Cloned browser';
$lang['title_browser_owner']='Responsible person';
$lang['title_browser_search_field']='Field to display as the record\'s title when a record is shown in site search results';
$lang['title_browsers']='Browsers';
$lang['title_data']='List data';
$lang['title_display']='Displayed';
$lang['title_export_file']='Export to host';
$lang['title_export_file_encoding']='Character-encoding of exported content';
$lang['title_field_identity']='Identifier';
$lang['title_form_fields']='The form\'s visible fields';
$lang['title_form_name']='Data source (PowerForms form)';
$lang['title_list_cssfile']='File containing styles for data lists';
$lang['title_move']='Change order';
$lang['title_oldmodule_data']='Use data from FormBuilder/Browser modules';
$lang['title_onchange_notices']='Notice after record change';
$lang['title_owned_forms'] = 'Enable user-specific browsing';
$lang['title_records']='Submissions';
$lang['title_related_form']='Related form';
$lang['title_pagerows']='Default rows-per-page';
$lang['title_sort']='Sortable';
$lang['title_strip_on_export']='Strip HTML tags on export';
$lang['title_submit_when']='Submitted';
$lang['title_submitted_as']='\'%s\' form submission';
$lang['title_submitted_edit']='Edit \'%s\' form submission';
$lang['title_uploads_path']='Sub-directory for module-specific file uploads';

$lang['up']='Move up';
$lang['update']='Update Browser';
$lang['updated']='updated';
$lang['updated2']='Updated %s.';

$lang['view']='View';
$lang['you_need_permission']='To access this, you need permission "%s"';

$lang['help_module']= <<<EOS
<h3>What does this module do?</h3>
<p>It allows authorised users to review and modify data recorded by the
PowerForms module for submitted forms.</p>
<h3>How is it used?</h3>
<p>In the CMSMS admin Content menu, there should be an item labelled
'Forms&nbsp;Data&nbsp;Browser'. Click on that. On the displayed page, there are
links and inputs by which to add a new browser, or configure module settings.</p>
<h4>Create a browser</h4>
<p>Click on a 'Add browser' link. During browser creation, you must select a
PowerForms form whose results the browser will display.</p>
<h4>Administering browser data</h4>
<p>By clicking on the 'inspect' icon next to a browser in the list, you can
add/edit/delete/export records.</p>
<h4>List styling</h4>
<p>Recognised classes are<ul>
<li>table_sort</li>
<li>SortAble for sortable columns</li>
<li>SortUp for the column sorted ascending</li>
<li>SortDown for the column sorted descending</li>
<li>row1s and row2s for alternate rows in the sorted column</li>
</ul></p>
<p>For example, the following represents the default settings:
<pre>%s</pre>
<p>A light-colored version of each sort-icon is provided, for use with dark themes.</p>
<h3>Requirements</h3>
<ul>
<li>CMS Made Simple 1.9+</li>
<li>PHP 5.2+</li>
<li>PHP extensions mbstring, curl, zip. Also Memcache if possible!</li>
<li>TODO</li>
</ul>
<h3>Support</h3>
<p>This module is provided as-is. Please read the text of the license for the full disclaimer.</p>
<p>There are a few resources available to help you with it:</p>
<ul>
<li>for the latest version of this module, FAQs, or to file a bug report, please visit the CMS Made Simple  <a href="http://dev.cmsmadesimple.org/powerbrowse">Developer Forge</a>;</li>
<li>discussion of this module might be found in the CMS Made Simple <a href="http://forum.cmsmadesimple.org">Forum;</a></li>
<li>perhaps you might have some success emailing the author directly.</li>
</ul>
<h3>Copyright and license</h3>
<p>Copyright &copy; 2011-2015, Tom Phane &lt;tpgww@onepost.net&gt;.<br />
Derived in part from FormBrowser module, copyright &copy; 2006-2011, Samuel Goldstein &lt;sjg@cmsmodules.com&gt;.<br />
All rights reserved.</p>
<p>This module has been released under the <a href="http://www.gnu.org/licenses/licenses.html#AGPL">GNU Affero General Public License</a> version 3. The module must not be used otherwise than in accordance with that licence.</p>
EOS;

?>
