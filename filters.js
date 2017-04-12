/**
 * Filters
 *
 * Plugin that adds a new tab to the settings section to create client-side e-mail filtering.
 *
 * @version 2.1.6
 * @author Roberto Zarrelli <zarrelli@unimol.it>
 * @author Chris Simon <info@decomputeur.nl> from version 2.1.3
 */


if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    var tab = $('<span>').attr('id', 'settingstabpluginfilters').addClass('tablink');    
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.filters').html(rcmail.gettext('filters', 'filters')).appendTo(tab);    
        
    // add button and register command
    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.filters-delete', function(){ rcmail.goto_url('plugin.filters-delete') }, true);    
    rcmail.register_command('plugin.filters-save', function(){ 
      var input_searchstring = rcube_find_object('_searchstring');      
      if (input_searchstring && input_searchstring.value=='') {
          alert(rcmail.gettext('nosearchstring','filters'));          
          input_searchstring.focus();
      }
      else
        rcmail.gui_objects.filtersform.submit();
    }, true);
  })
}







