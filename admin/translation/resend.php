<?php // Allows the admin to configure Forums translations

	//define('NO_OUTPUT_BUFFERING', true);

    require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
    require_once($CFG->libdir.'/adminlib.php');
	require_once('resend_form.php');
 

	require_login();
	admin_externalpage_setup('translation');
	

    $context = context_system::instance();
    require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

 
 
 
 
echo $OUTPUT->header();

//Instantiate simplehtml_form 
$mform = new simplehtml_form();	


//Instantiate simplehtml_form 
$mform = new simplehtml_form();
 
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
 
  //Set default data (if any)
  $mform->set_data($toform);
  //displays the form
  $mform->display();
}
	
//echo $OUTPUT->box_end();
echo $OUTPUT->footer();

// Funciones del módulo ///
function forum_parameters($forumid)
{
	global $DB;

	$retval="";
	$query="SELECT * from trans_forum_parameter WHERE forum_id=$forumid ORDER BY varname ASC;";
	$r = $DB->get_recordset_sql( $query );
	
	foreach( $r as $rows=>$row )  
	{
		$retval.=$row->varname."=".$row->val."";
		echo $retval;
	}
	return $retval;
}

function selectbox_forum($defaultid, $formname)
{
	Global $CFG, $DB;
	$retval="<select name=\"$formname\" style=\"width: 20em;\">\n";

	$sql = "SELECT f.id, f.name, f.intro, shortname, fullname 
		FROM forum f
		LEFT JOIN course c 
			ON f.course=c.id";
	
	
	$forums = $DB->get_recordset_sql( $sql );
	 
        foreach( $forums as $forumid=>$forum ) { 
		if($forum->id == $defaultid)
		{
			$selected=" selected=\"selected\" ";
		}
		else
		{
			$selected=" ";
		}

		$retval.="<option $selected value=\"".$forum->id."\">"
			.$forum->name
			."|".$forum->intro
			."|".$forum->shortname
			."|".$forum->fullname
			."</option>\n";
	}
        $retval = $retval . "</select>";
	return $retval;
}

 
function selectbox_idioma($defaultlang, $formname)
{
	Global $CFG;

/*	$idiomas1=array('es','en','fr','ht','ar','sq','af','be','bg','ca','zh','zh-CN','zh-TW','hr','cs','da','nl','et','tl','fi','gl','de','el',		
		  'iw','hi','hu','is','id','ga','it','ja','ko','lv','lt','mk','ms','mt','no','fa','pl','pt','ro','ru','sr','sk','sl','sw',
		 'sv','th','tr','uk','vi','cy','yi'); 
    */
$idiomas=array( array('code' =>'es', 'name' =>'Español'),
				array('code' =>'en', 'name' =>'English'),
				array('code' =>'fr', 'name' =>'Français'),
				array('code' =>'ht', 'name' =>'Haitian Creol'),
				array('code' =>'ar', 'name' =>'Arabic'),
				array('code' =>'sq', 'name' =>'Albanian'),
				array('code' =>'af', 'name' =>'Afrikaans'),
				array('code' =>'be', 'name' =>'Belarusian'),
				array('code' =>'bg', 'name' =>'Bulgarian'), 
				array('code' =>'ca', 'name' =>'Catalan'),
				array('code' =>'zh', 'name' =>'Chinese'),
				array('code' =>'zh-CN', 'name' =>'Chinese (Simplified)'),
				array('code' =>'zh-TW', 'name' =>'Chinese (Traditional)'),
				array('code' =>'hr', 'name' =>'Croatian'),
				array('code' =>'cs', 'name' =>'Czech'),
				array('code' =>'da', 'name' =>'Danish'),
				array('code' =>'nl', 'name' =>'Dutch'),
				array('code' =>'et', 'name' =>'Estonian'),
				array('code' =>'tl', 'name' =>'Filipino'),
				array('code' =>'fi', 'name' =>'Finnish'),
				array('code' =>'gl', 'name' =>'Galician'),
				array('code' =>'de', 'name' =>'German'),
				array('code' =>'el', 'name' =>'Greek'),		
				array('code' =>'iw', 'name' =>'Hebrew'),
				array('code' =>'hi', 'name' =>'Hindi'),
				array('code' =>'hu', 'name' =>'Hungarian'),
				array('code' =>'is', 'name' =>'Icelandic'),
				array('code' =>'id', 'name' =>'Indonesian'),
				array('code' =>'ga', 'name' =>'Irish'),
				array('code' =>'it', 'name' =>'Italian'),
				array('code' =>'ja', 'name' =>'Japanese'),
				array('code' =>'ko', 'name' =>'Korean'),
				array('code' =>'lv', 'name' =>'Latvian'),
				array('code' =>'lt', 'name' =>'Lithuanian'),
				array('code' =>'mk', 'name' =>'Macedonian'),
				array('code' =>'ms', 'name' =>'Malay'),
				array('code' =>'mt', 'name' =>'Maltese'),
				array('code' =>'no', 'name' =>'Norwegian'),
				array('code' =>'fa', 'name' =>'Persian'),
				array('code' =>'pl', 'name' =>'Polish'),
				array('code' =>'pt', 'name' =>'Portuguese'),
				array('code' =>'ro', 'name' =>'Romanian'),
				array('code' =>'ru', 'name' =>'Russian'),
				array('code' =>'sr', 'name' =>'Serbian'),
				array('code' =>'sk', 'name' =>'Slovak'),
				array('code' =>'sl', 'name' =>'Slovenian'),
				array('code' =>'sw', 'name' =>'Swahili'),
				array('code' =>'sv', 'name' =>'Swedish'),
				array('code' =>'th', 'name' =>'Thai'),
				array('code' =>'tr', 'name' =>'Turkish'),
				array('code' =>'uk', 'name' =>'Ukrainian'),
				array('code' =>'vi', 'name' =>'Vietnamese'),
				array('code' =>'cy', 'name' =>'Welsh'),
				array('code' =>'yi', 'name' =>'Yiddish')  ); 
 

 				
	$retval="<select name=\"$formname\" style=\"width: 6em;\">\n";
	
        foreach( $idiomas as $lang ) { 
		if($lang['code'] == $defaultlang)
		{
			$selected=" selected=\"selected\" ";
		}
		else
		{
			$selected=" ";
		}

		$retval.="<option $selected value=\"".$lang['code']. "\">"
			.$lang['name']
			."</option>\n";
	  }
        $retval = $retval . "</select>";
	return $retval;
}



function selectbox_api($defaultapi, $formname)
{
	Global $CFG;

	$apis=array( array('cod'=>'Google', 'nam'=>'Google Translate'),
				 array('cod'=>'Microsoft', 'nam'=>'Microsoft Translator')); 
				
	$retval="<select name=\"$formname\" style=\"width: 10em;\">\n";
	
        foreach( $apis as $apiname ) { 
		if($apiname['cod'] == $defaultapi)
		{
			$selected=" selected=\"selected\" ";
		}
		else
		{
			$selected=" ";
		}

		$retval.="<option $selected value=\"".$apiname['cod']. "\">"
			.$apiname['nam']
			."</option>\n";
	  }
        $retval = $retval . "</select>";
	return $retval;
}



function envprint()
{
	
	print "POST:<br>\n";
	foreach($_POST as $var=>$val)
	{
		print "$var: \"$val\"<br>\n";
	}
}

//echo $OUTPUT->footer();

?>
