<?php
/**
 * Main Elementor validation functions
 *
 */

add_action( 'elementor_pro/forms/validation', 'efas_validation_process' , 10, 2 );
function efas_validation_process ( $record, $ajax_handler ) {
  $spamcounter = get_option( 'spamcounter' ) ? get_option( 'spamcounter' ) : 0;
  $error_message = cfas_get_error_text();
  $spam = false;
  $reason ="";
  // ip
  $ip = efas_getRealIpAddr();
  
  // Country IP Check 
  $CountryCheck = CountryCheck($ip,$spam,$reason);
  $spam = $CountryCheck['spam'];
  $reason = $CountryCheck['reason'];

  $NeedPageurl =  get_option( 'NeedPageurl' );   
  if ( efas_get_spam_api('block_empty_source') ){
    $NeedPageurl = $NeedPageurl ? $NeedPageurl : efas_get_spam_api('block_empty_source')[0];
  }

  if( !array_key_exists('referrer', $_POST ) && $NeedPageurl ){
      $spam = true;
      $reason = "Page source url is empty";
  }
  
  /*
  // later
  if ( get_option( 'spampixel' ) && false ) {
	if (false === get_transient('spx_allow_' .$ip)) {
        update_option( 'spamcounter', ++$spamcounter );
        efas_add_to_log($type = "Spampixel","User look like robot", $_POST);
		wp_die('User look like robot, try again ');
	}
  }*/
  
 
  // AbuseIPDB API  (Thanks to @josephcy95)
  $abuseipdb_api = get_option('abuseipdb_api') ? get_option('abuseipdb_api') : false;
  $pabuseipdb_score = get_option('abuseipdb_score');
  //Check if have abuseipdb_api in the API Setting page (WpMaspik)
  if ( efas_get_spam_api('abuseipdb_api') ){
    $abuseipdb_api_json = null !== efas_get_spam_api('abuseipdb_api') ? efas_get_spam_api('abuseipdb_api') : false;
    $abuseipdb_api = $abuseipdb_api ? $abuseipdb_api : $abuseipdb_api_json; // Site setting is stronger
    $abuseipdb_score_json = null !== efas_get_spam_api('abuseipdb_score') ? efas_get_spam_api('abuseipdb_score') : '50';
    $pabuseipdb_score = $pabuseipdb_score ? $pabuseipdb_score : $abuseipdb_score_json; // Site setting is stronger
  }
  
  if (($abuseipdb_api != false) && ($spam != true)) {
    $abuseconfidencescore = check_abuseipdb($ip);
    if ($abuseconfidencescore >= (int)$pabuseipdb_score) {
      $spam = true;
      $reason = "AbuseIPDB Risk: $abuseconfidencescore ";
    }
  }

  // Proxycheck.io Risk Check  (Thanks to @josephcy95)
  $proxycheck_io_api = get_option('proxycheck_io_api') ? get_option('proxycheck_io_api') : false;
  $proxycheck_io_risk = get_option('proxycheck_io_risk');
  //Check if have proxycheck_io_api in the API Setting page (WpMaspik)
  if ( null !== efas_get_spam_api('proxycheck_io_api') ){
    $proxycheck_io_api_json = null !== efas_get_spam_api('proxycheck_io_api') ? efas_get_spam_api('proxycheck_io_api') : false;
    $proxycheck_io_risk_json = null !== efas_get_spam_api('proxycheck_io_risk') ? efas_get_spam_api('proxycheck_io_risk') : false;
    $proxycheck_io_api = $proxycheck_io_api ? $proxycheck_io_api : $proxycheck_io_api_json; // Site setting is stronger
    $proxycheck_io_risk = $proxycheck_io_risk ? $proxycheck_io_risk : $proxycheck_io_risk_json; // Site setting is stronger
  }

  if (($proxycheck_io_api != false) && ($spam != true)) {
    $proxycheck_io_riskscore = check_proxycheckio($ip);
    if ($proxycheck_io_riskscore >= (int)$proxycheck_io_risk) {
      $spam = true;
      $reason = "Proxycheck.io Risk: $proxycheck_io_riskscore";
    }
  }

  if ( $spam ) {
    update_option( 'spamcounter', ++$spamcounter );
    efas_add_to_log($type = "General",$reason, $_POST );
    die('ip_country_blacklist');
  }
  
}

// Validate the Text fields.
add_action( 'elementor_pro/forms/validation/text', function( $field, $record, $ajax_handler ) {
	$spamcounter = get_option( 'spamcounter' ) ? get_option( 'spamcounter' ) : 0;
  
  	$field_value = strtolower($field['value']); 
    if(!$field_value){
      return;
    }
  	$error_message = cfas_get_error_text();
    $text_blacklist = get_option( 'text_blacklist' ) ? efas_makeArray(get_option('text_blacklist') ) : array('eric jones');
  	if ( efas_get_spam_api() ){
    	$text_blacklist_json =  efas_get_spam_api();
      	$text_blacklist = array_merge($text_blacklist, $text_blacklist_json);
    }  
	if( is_array($text_blacklist) ){
       foreach($text_blacklist as $bad_string) {
          if( efas_is_field_value_equwl_to_string($bad_string, $field_value) ) {
            $reason = "Input $field_value is block";
            update_option( 'spamcounter', ++$spamcounter );
              efas_add_to_log($type = "text",$reason, $_POST );
            $ajax_handler->add_error( $field['id'], $error_message );
            break;
          }
       }
    }
  
  // Max Characters In Text Field in the rgiht order
	$MaxCharacters_API = false;
  	if ( efas_get_spam_api('MaxCharactersInTextField') ){
    	$MaxCharacters_API = efas_get_spam_api('MaxCharactersInTextField')[0];
    }
    $MaxCharacters = get_option( 'MaxCharactersInTextField' ) ? get_option( 'MaxCharactersInTextField' ) : $MaxCharactersInTextField ;
    $CountCharacters = strlen($field_value);
	if( $MaxCharacters && $CountCharacters ){
        if($MaxCharacters < $CountCharacters ) {
          update_option( 'spamcounter', ++$spamcounter );
          efas_add_to_log($type = "text","More then $MaxCharacters characters", $_POST);          
          $ajax_handler->add_error( $field['id'], $error_message );
        }
    }
}, 10, 3 );

// Validate the Email fields.
add_action( 'elementor_pro/forms/validation/email', function( $field, $record, $ajax_handler ) {
	$spamcounter = get_option( 'spamcounter' ) ? get_option( 'spamcounter' ) : 0;
  	$field_value = strtolower($field['value']); 
    if(!$field_value){
      return;
    }
  
  	$error_message = cfas_get_error_text();
    $text_blacklist = efas_makeArray( get_option( 'emails_blacklist' ) );
	if ( efas_get_spam_api('email_field') ){
    	$blacklist_json = efas_get_spam_api('email_field') ;
      	$text_blacklist = array_merge($text_blacklist, $blacklist_json);
    }
  	$spam = false;
    foreach ($text_blacklist as $bad_string) {         
      if(isset($bad_string[0]) && $bad_string[0] === "/" ){ // check
        if ( preg_match( $bad_string, $field_value ) ) {
          $spam = true;
        }
      }
      $spam = efas_is_field_value_equwl_to_string($bad_string, $field_value) ? true : $spam ;
      if($spam){
      	break;
      }
   }
	// check if spam email-domain enter, like: @xyz.com , @gmail.com ...
  	$spam = cfes_is_spam_email_domain($field_value,$text_blacklist)  ? true : $spam;

    if( $spam) {
      update_option( 'spamcounter', ++$spamcounter );
      efas_add_to_log($type = "email","Email $field_value is block", $_POST);
      $ajax_handler->add_error( $field['id'], $error_message );
    }

}, 10, 3 );

// preg_match the Tel field to the given format.
add_action( 'elementor_pro/forms/validation/tel', function( $field, $record, $ajax_handler ) {
  	$spamcounter = get_option( 'spamcounter' ) ? get_option( 'spamcounter' ) : 0;
  	$field_value = $field['value']; 
    $tel_formats = get_option( 'tel_formats' );

    if($field_value == "" || !$field_value  || !$tel_formats){
      return;
    }
    $tel_formats = explode( "\n", str_replace("\r", "", $tel_formats) );
  	if ( efas_get_spam_api('tel_formats') ){
    	$blacklist_json = efas_get_spam_api('tel_formats') ;
      	$tel_formats = array_merge($tel_formats, $blacklist_json);
    }
  
  	$error_message = cfas_get_error_text();
    $valid = true;

    if( is_array($tel_formats) ){
      $valid = false;
      foreach ($tel_formats as $format) {
        // Match this format XXX-XXX-XXXX, 123-456-7890 -- like: [0-9]{3}-[0-9]{3}-[0-9]{4}
        //$field_value = "050-99755794";
        //$format = "/^(?:(?:(\+?972|\(\+?972\)|\+?\(972\))(?:\s|\.|-)?([1-9]\d?))|(0\d{1,2}))(?:\s|\.|-)?([^0\D]{1}\d{2}(?:\s|\.|-)?\d{4})$/";
        if ( preg_match( $format, $field_value ) ) {
          $valid = true;
          break;
        }
      }
      if(!$valid){
         efas_add_to_log($type = "tel","Telephone number $field_value not feet the format $format ", $_POST);
         $ajax_handler->add_error( $field['id'], $error_message );
      }
    } 
}, 10, 3 );
           
// Validate the Textarea field.
add_action( 'elementor_pro/forms/validation/textarea', function( $field, $record, $ajax_handler ) {
	$spamcounter = get_option( 'spamcounter' ) ? get_option( 'spamcounter' ) : 0;
  	$field_value = strtolower($field['value']); 

    if(!$field_value){
      return;
    }
  	$error_message = cfas_get_error_text();
    $textarea_blacklist = get_option( 'textarea_blacklist' ) ? efas_makeArray(get_option( 'textarea_blacklist' )) : array();

  	if ( efas_get_spam_api('textarea_field') ){
    	$blacklist_json = efas_get_spam_api('textarea_field') ;
      	$textarea_blacklist = array_merge($textarea_blacklist, $blacklist_json);
    }

  	foreach ($textarea_blacklist as $bad_string) {
        if($bad_string[0] === "[" ){ // check
          $search  = array('[', ']');
          $bad_string = str_replace($search, "", $bad_string);
          $bad_string = "url" || "name" || "description" ? get_bloginfo($bad_string) : "Error - Shortcode not exsist";
        }
        if(efas_is_field_value_exist_in_string($bad_string, $field_value)) {
          update_option( 'spamcounter', ++$spamcounter );
          efas_add_to_log($type = "textarea","field_value include: $bad_string |", $_POST);
          $ajax_handler->add_error( $field['id'], $error_message );
        }
    }
	//  lang_needed
    $lang_needed = get_option( 'lang_needed' ) ? get_option( 'lang_needed' ) : array();
  	/*if ( efas_get_spam_api( 'lang_needed') ){
    	$lang_needed_api = efas_get_spam_api('lang_needed') ;
      	$lang_needed = array_merge($lang_needed, $lang_needed_api);
    }*/

    if( $lang_needed && !efas_is_lang($lang_needed,$field_value) ){
          update_option( 'spamcounter', ++$spamcounter );
          efas_add_to_log($type = "textarea","Needed language is missing ", $_POST);
          $ajax_handler->add_error( $field['id'], $error_message );
    }
  //  lang_forbidden
    $lang_forbidden = get_option( 'lang_forbidden' ) ? get_option( 'lang_forbidden' ) : array();
  	/*if ( efas_get_spam_api( 'lang_forbidden') ){
    	$lang_needed_api = efas_get_spam_api('lang_forbidden') ;
      	$lang_forbidden = array_merge($lang_forbidden, $lang_needed_api);
    }*/
    if( $lang_forbidden && efas_is_lang($lang_forbidden,$field_value) ){
          update_option( 'spamcounter', ++$spamcounter );
          efas_add_to_log($type = "textarea","Forbidden language is exists ", $_POST);
          $ajax_handler->add_error( $field['id'], $error_message );
    }
  
  	//contain_links (in right order)
  	$max_links_json =  is_array( efas_get_spam_api( 'contain_links') ) ? efas_get_spam_api( 'contain_links')[0] : false;

    $max_links =  get_option( 'contain_links' );// ?  get_option( 'contain_links' ) : $max_links_json;
    if ( $max_links ) {
      $reg_exUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
	  $num_links = preg_match_all( $reg_exUrl, $field_value );
		if ( $num_links >= $max_links ) {
              update_option( 'spamcounter', ++$spamcounter );
              efas_add_to_log($type = "textarea","contain more then  $max_links links", $_POST);
			  $ajax_handler->add_error( $field['id'], $error_message );
		}
	}

}, 10, 3 );



//
//add_action( 'elementor_pro/forms/render/item', 'cfas_add_spampixel_to_elementor', 10, 3 );
function cfas_add_spampixel_to_elementor( $item, $item_index, $widget ){
  if ( 1 === $item_index ) {
     cfas_add_spampixel_to_form();
  }
  return $item;
}