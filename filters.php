<?php

/**
 * Filters
 *
 * Plugin that adds a new tab to the settings section to create client-side e-mail filtering.
 *
 * @version 2.1.4
 * @author Roberto Zarrelli <zarrelli@unimol.it> 
 * @author Chris Simon <info@decomputeur.nl> from version 2.1.3
 *   
 */

 
class filters extends rcube_plugin{

  public $task = 'login|mail|settings';   
  private $rc;    
  private $searchstring = array();    
  private $destfolder = array();
  private $msg_uids = array();    	    
  
    
  function init(){
    $this->rc = rcmail::get_instance();	
	$this->load_config('config.inc.php.dist');
	$this->load_config('config.inc.php');
       
		if($this->rc->task == 'mail')           		  
			$this->add_hook('messages_list', array($this, 'filters_checkmsg'));		
    else if ($this->rc->task == 'settings'){
        $this->register_action('plugin.filters', array($this, 'filters_init'));
        $this->register_action('plugin.filters-save', array($this, 'filters_save'));
        $this->register_action('plugin.filters-delete', array($this, 'filters_delete'));                 
        $this->add_texts('localization/', array('filters','nosearchstring'));       
        $this->rc->output->add_label('filters');
        $this->include_script('filters.js');      
    }
    else if ($this->rc->task == 'login'){
      if ($this->rc->config->get('autoAddSpamFilterRule', true))
        $this->add_hook('login_after', array($this, 'filters_addMoveSpamRule'));
    }
  }  
    
  function filters_checkmsg($mlist){
		$this->add_texts('localization/');
		$user = $this->rc->user;
		$imap = $this->rc->imap;
		$open_mbox = $imap->get_mailbox_name();								
				
		// does not consider the messages already in the trash
    if ($open_mbox == $this->rc->config->get('trash_mbox'))
		  return;
          		    
    //load filters
    $arr_prefs = $this->rc->config->get('filters', array());
            
                        
    foreach ($arr_prefs as $key => $saved_filter){      
      if ($saved_filter['destfolder'] != $open_mbox && $imap->mailbox_exists($saved_filter['destfolder'])){
        // destfolder#message filter
        $saved_filter['searchstring'] = html_entity_decode($saved_filter['searchstring']);
        $this->searchstring[$saved_filter['whatfilter']][$saved_filter['searchstring']] = $saved_filter['destfolder']."#".$saved_filter['messages'];
      }                    
    }
    
    	
    // if there aren't filters return
    if(!count($arr_prefs) || !count($this->searchstring) || !isset($mlist['messages']) || !is_array($mlist['messages'])) 
      return;
                                  
    // scan the messages
    foreach($mlist["messages"] as $message){    
      $this->filters_search($message, 'from');
      $this->filters_search($message, 'to');
      $this->filters_search($message, 'cc');
      $this->filters_search($message, 'subject');            
    }        
                       
    // move the filtered messages            
    if (count($this->destfolder) > 0){    
      foreach ($this->destfolder as $dfolder){                                          
        $uids = array();        
        foreach ($this->msg_uids[$dfolder] as $muids){          
          $uids[] = $muids;
        }                          
        if (count($uids)){                    
				  $imap->move_message($uids, $dfolder, $open_mbox);
				  $this->rc->output->show_message($this->gettext('msg_moved_by_rule'), 'confirmation');
        }                                  
      }            
      // refresh      
			$this->api->output->command('list_mailbox');
			$this->api->output->send();				
			$this->api->output->command('getunread');
			$this->api->output->send();                      
    }
            			
  }
  

  function filters_init(){    
    $this->add_texts('localization/');
    $this->register_handler('plugin.body', array($this, 'filters_form'));    
    $this->rc->output->set_pagetitle($this->gettext('filters'));
    $this->rc->output->send('plugin');    
  }    
  
  function filters_save(){
    $user = $this->rc->user;
  
    $this->add_texts('localization/');
    $this->register_handler('plugin.body', array($this, 'filters_form'));
    $this->rc->output->set_pagetitle($this->gettext('filters'));
    
    $searchstring = trim(get_input_value('_searchstring', RCUBE_INPUT_POST, true));
    $destfolder = trim(get_input_value('_folders', RCUBE_INPUT_POST, true));
    $whatfilter = trim(get_input_value('_whatfilter', RCUBE_INPUT_POST, true));
    $messages = trim(get_input_value('_messages', RCUBE_INPUT_POST, true));
    
    if ($searchstring == "")
      $this->rc->output->command('display_message', $this->gettext('nosearchstring'), 'error');  
    else{    
      $new_arr['whatfilter'] = $whatfilter;
      $new_arr['searchstring'] = htmlentities($searchstring);
      $new_arr['destfolder'] = $destfolder; 
      $new_arr['messages'] = $messages;     
      $arr_prefs = $user->get_prefs();                    
      $arr_prefs['filters'][] = $new_arr;
      if ($user->save_prefs($arr_prefs))
        $this->rc->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
      else
        $this->rc->output->command('display_message', $this->gettext('unsuccessfullysaved'), 'error');      
    }
    rcmail_overwrite_action('plugin.filters');
    $this->rc->output->send('plugin');    
  }
  
  function filters_delete(){
    $user = $this->rc->user;

    $this->add_texts('localization/');
    $this->register_handler('plugin.body', array($this, 'filters_form'));
    $this->rc->output->set_pagetitle($this->gettext('filters'));
    
    if (isset($_GET[filterid])){
      $filter_id = $_GET[filterid];      
      $arr_prefs = $user->get_prefs();      
      $arr_prefs['filters'][$filter_id] = '';      
      $arr_prefs['filters'] = array_diff($arr_prefs['filters'], array(''));
      if ($user->save_prefs($arr_prefs))
        $this->rc->output->command('display_message', $this->gettext('successfullydeleted'), 'confirmation');
      else
        $this->rc->output->command('display_message', $this->gettext('unsuccessfullydeleted'), 'error');
    }
    
    rcmail_overwrite_action('plugin.filters');
    $this->rc->output->send('plugin');      
  }    
      

  function filters_form(){
        		    
    $this->rc->imap_connect();
  
    $table = new html_table(array('cols' => 2));
    $table->add('title', Q($this->gettext('whatfilter').":"));
    
    $select = new html_select(array('name' => '_whatfilter', 'id' => 'whatfilter'));
    $select->add($this->gettext('from'), 'from');
    $select->add($this->gettext('to'), 'to');        
    $select->add($this->gettext('cc'), 'cc');
    $select->add($this->gettext('subject'), 'subject');
    $table->add('', $select->show($this->gettext('from')));
        
    $table->add('title', Q($this->gettext('searchstring').":"));
    $inputfield = new html_inputfield(array('name' => '_searchstring', 'id' => 'searchstring'));
    $table->add('', $inputfield->show(""));
    
    $table->add('title', Q($this->gettext('moveto').":"));            
    $select = rcmail_mailbox_select(array('name' => '_folders', 'id' => 'folders'));    
    $table->add('title',  $select->show());
    
    # new option: all, read and unread messages
    $table->add('title', Q($this->gettext('messagecount').":"));
    $select = new html_select(array('name' => '_messages', 'id' => 'messages'));
    $select->add($this->gettext('all'), 'all');
    $select->add($this->gettext('unread'), 'unread');        
    $select->add($this->gettext('markread'), 'markread');
    $table->add('', $select->show($this->gettext('all')));    
        
    // get mailbox list    
    $a_folders = $this->rc->imap->list_mailboxes('', '*');
    $delimiter = $this->rc->imap->get_hierarchy_delimiter();
    $a_mailboxes = array();
    foreach ($a_folders as $folder)
      rcmail_build_folder_tree($a_mailboxes, $folder, $delimiter);    
    
    // load saved filters    
    $user = $this->rc->user;    
    $arr_prefs = $user->get_prefs();                    
    $i = 1;
    $flag=false;
    $table2 = new html_table(array('cols' => 2));
    foreach ($arr_prefs['filters'] as $key => $saved_filter){
      $flag=true;
      $folder_id = $saved_filter['destfolder'];
      $folder_name = "";
      if (strtoupper($folder_id) == 'INBOX')
        $folder_name = rcube_label('inbox');
      else{          
        foreach ($a_mailboxes as $folder => $vet){
          if ($vet['id'] == $folder_id){
            $folder_name = $vet['name'];
            break;
          }
        }
      }
      $messages = $saved_filter['messages'];  
                                 
      $msg = $i." - ".$this->gettext('msg_if_field')." <b>".$this->gettext($saved_filter['whatfilter'])."</b> ".$this->gettext('msg_contains')." <b>".$saved_filter['searchstring']."</b> ".$this->gettext('msg_move_msg_in')." <b>".$folder_name."</b> "."(".$this->gettext('messagecount').": ".$this->gettext($saved_filter['messages']).")";        
      $table2->add('title',$msg);        
      $dlink = "<a href='./?_task=settings&_action=plugin.filters-delete&filterid=".$key."'>".$this->gettext('delete')."</a>";                
      $table2->add('title',$dlink);
      $i++;      
    } 

    if (!$flag){
      $table2->add('title',Q($this->gettext('msg_no_stored_filters')));        
    }      
                
    $out = html::div(array('class' => 'box'),
        html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('filters')) .
        html::div(array('class' => 'boxcontent'), $table->show() . 
        html::p(null,
            $this->rc->output->button(array(
                'command' => 'plugin.filters-save',
                'type' => 'input',
                'class' => 'button mainaction',
                'label' => 'save'
        )))));
        
    $out .=  html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('storedfilters')). html::div(array('class' => 'uibox listbox scroller','style'=>'margin-top:205px;'),
     
        html::div(array('class' => 'boxcontent'), $table2->show() ));
    
    $this->rc->output->add_gui_object('filtersform', 'filters-form');
    
    return $this->rc->output->form_tag(array(
        'id' => 'filters-form',
        'name' => 'filters-form',
        'method' => 'post',
        'action' => './?_task=settings&_action=plugin.filters-save',
    ), $out);
            
  }
  
  
  function filters_search($message, $whatfilter){
  
    // check if a message has been read
    if (isset($message->flags['SEEN']) && $message->flags['SEEN'])
      $msg_read = 1;
    else
      $msg_read = 0;      
    
    
    if (isset($this->searchstring[$whatfilter])){
      
      foreach ($this->searchstring[$whatfilter] as $from => $dest){
        
        $arr = explode("#",$dest);
        $destination = $arr[0];
        $msg_filter = $arr[1];
                  
        if ($msg_filter != "all"){                    
          if ($msg_read && $msg_filter != "markread") continue;
          if (!$msg_read && $msg_filter != "unread") continue;
        }        
        
        switch ($whatfilter){
          case 'from':
            $field = $message->from;
            break;
          case 'to':
            $field = $message->to;
            break;
          case 'cc':
            $field = $message->cc;
            break;
          case 'subject':
            $field = $message->subject;
            break;
          default:
            $field = "";            
        }                                
                            
        if ($this->filters_searchString($field, $from) !== FALSE){            
          $this->msg_uids[$destination][] = $message->uid;            
          if (!in_array($destination, $this->destfolder))
            $this->destfolder[] = $destination;
        }
                                
      }
                    
    }
        
  }
  
  
  function filters_searchString($msg,$stringToSearch){
    $ret = FALSE;
    
    $ciSearch = $this->rc->config->get('caseInsensitiveSearch', true);
        
    if ($ciSearch){
      $tmp = stripos($msg, $stringToSearch);
    }
    else{
      $tmp = strpos($msg, $stringToSearch);
    }
        
    if ($tmp !== FALSE){
      $ret = TRUE;
    }
    else{
      if ($this->rc->config->get('decodeBase64Msg', true) === TRUE){
        // decode and search BASE64 msg
        $decoded_str = base64_decode($msg);
        if ($decoded_str !== FALSE){

          if ($ciSearch){
            $tmp = stripos($decoded_str, $stringToSearch);
          }
          else{
            $tmp = strpos($decoded_str, $stringToSearch);
          }
                        
          if ($tmp !== FALSE){
            $ret = TRUE;
          }
        }
      }
    }    
    return $ret;
  }
  
  
  function filters_addMoveSpamRule(){
    
      $user = $this->rc->user;
      
      $searchstring = $this->rc->config->get('spam_subject', '***SPAM***');
      $destfolder = $this->rc->config->get('junk_mbox', null);
      $whatfilter = "subject"; 
      $messages = "all";
            
      //load filters
      $arr_prefs = $this->rc->config->get('filters', array());            
            
      // check if the rule is already enabled  
      $found = false;                              
      foreach ($arr_prefs as $key => $saved_filter){
        if ($saved_filter['searchstring'] == $searchstring && $saved_filter['destfolder'] == $destfolder && $saved_filter['whatfilter'] == $whatfilter && $saved_filter['messages'] == $messages){
          $found = true;
        }               
      }        
      
      if (!$found && $destfolder !== null && $destfolder !== ""){
        $new_arr['whatfilter'] = $whatfilter;
        $new_arr['searchstring'] = $searchstring;
        $new_arr['destfolder'] = $destfolder; 
        $new_arr['messages'] = $messages;     
        $arr_prefs = $user->get_prefs();                    
        $arr_prefs['filters'][] = $new_arr;
        $user->save_prefs($arr_prefs);            
      }                          
  }
      
}