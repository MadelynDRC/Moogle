<?php
/*  Este programa es software libre: usted puede redistribuirlo y/o
    modificarlo bajo los términos de la Licencia Pública General GNU publicada
    por la Fundación para el Software Libre, ya sea la versión 3
    de la Licencia, o cualquier versión posterior.

    Este programa se distribuye con la esperanza de que sea útil, pero
    SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
    MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
    Consulte los detalles de la Licencia Pública General GNU para obtener
    una información más detallada.

    Debería haber recibido una copia de la Licencia Pública General GNU
    junto a este programa.
    En caso contrario, consulte <http://www.gnu.org/licenses/>.
   
    POR FAVOR CONSERVE ESTA NOTA SI EDITA ESTE ARCHIVO

    Desarrollado por y para:
		Funredes

    Desarrollo
		Madelyn Del Rosario Cruz
	
    Santo Domingo - República Dominicana - 2013
 **/

defined('MOODLE_INTERNAL') || die();

/***
 * El Archivo liblisto.php contiene las rutinas que ejecutan la moderación y la traducción automática de los foros.
 *****/

/** Moodle-Listo: include requerido para la traducción utilizando el api de google **/
require_once('utiltradforo.php');
require_once('lib.php');
require_once('microsoftTranslateCurl.php');
require_once('microsoft/config.inc.php');
//require_once('MicrosoftTranslator.php');
//require_once('AccessTokenAuthentication.php');
/**/

/**
 * Moddle-Listo: Function to handle messages for moderation.
 *
 *  *  Are there any unmailed(implicates unmoderated) posts?
 *	 First move them into the moderator posts table
 *	 If they were confirmed, put them back as "to be mailed".
 *	 	
 *	 For the experiments with moderation handling, let's stop here for now.
 *	 Or comment out the "return", to succeed normally with the cron job.
 *	return;
 *
 **/
function moderation_workflow()
{
    global $CFG, $USER, $forum_moderation_dbh, $DB;

    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    $starttime = $endtime - 48 * 3600;   // Two days earlier

	/* Moodle-Listo: Let's first move new threads in translated single-language forums to 
	* original multilanguage forums.
	* */ 

        $query = 'select forum_posts.id, discussion, forum, subject, forum_id_orig, course_forum.course 
				FROM forum_posts LEFT JOIN forum_discussions ON 
				forum_posts.discussion=forum_discussions.id 
				LEFT JOIN forum_translate_to ON forum=forum_id_translated 
				LEFT JOIN forum course_forum ON forum_id_orig=course_forum.id 
			WHERE parent = 0 
			AND forum IN (select forum_id_translated from forum_translate_to) 
			AND forum_posts.id NOT IN (select target_id from forum_post_id_translated) 
			ORDER BY forum_posts.id ;' ;
        
     $sth = $DB->get_records_sql($query);
	
     if (!empty($sth))
      {
		//foreach($$sth as $row)
			foreach( $sth as $foros=>$row ) 
			{   
				/*$sql = "update {forum_discussions} "
							."set forum=".$row->forum_id_orig . ", "
							." course=".$row->course
							." where id=".$row->discussion.";";
						 //Revisar
						  $sth=$DB->execute($sql); */
				$record = new stdClass();
				$record->id				= $row->discussion;
				$record->forum			= $row->forum_id_orig;
				$record->course			= $row->course;
				
				$DB->update_record('forum_discussions',  $record, false);		  
						  
			}

			/*foreach($updates as $sql)
			{
				
			 $sth=$DB->execute($sql); 
			}*/
	}
	# This is a nearly verbatim copy of forum_get_unmailed_posts().
     if (!empty($CFG->forum_enabletimedposts)) {
        if (empty($now)) {
            $now = time();
        }
        $timedsql = "AND (d.timestart < $now AND (d.timeend = 0 OR d.timeend > $now))";
       // $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ? ))";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }

	$cols=array("id", "discussion", "parent", "userid", "created", "modified", "mailed", "subject", "message", "format", "attachment", "totalscore", "mailnow");


	/* Get the forum ID's involved in translation, to use that as SQL "IN" query right after. */
	$query=
		"SELECT forum_id_orig 
		FROM {forum_translate_to} 
			UNION 
				SELECT forum_id_translated 
				FROM {forum_translate_to};";
	
    $sth= $DB->get_records_sql($query); 
  
	foreach ($sth as $pid => $post)
	{
		$forumid_array[]=$post->forum_id_orig;
	}
    /*$notmailed_posts= get_records_sql("SELECT p.*, d.course, d.forum
                              FROM {$CFG->prefix}forum_posts p
                                   JOIN {$CFG->prefix}forum_discussions d ON d.id = p.discussion
                             WHERE p.mailed = 0
                                   AND p.created >= $starttime
                                   AND (p.created < $endtime OR p.mailnow = 1)
											  AND forum IN (".join(", ", $forumid_array).")
                                   $timedsql
                          ORDER BY p.modified ASC");*/
                        
		$notmailed_posts = $DB->get_records_sql("SELECT p.*, d.course, d.forum
                              FROM {forum_posts} p
                                   JOIN {forum_discussions} d ON d.id = p.discussion
                             WHERE p.mailed = 0
                                   AND p.created >= $starttime
                                   AND (p.created < $endtime  OR p.mailnow = 1)
											AND forum IN (".join(", ", $forumid_array).")
									$timedsql
                          ORDER BY p.modified ASC ;");                          
         
       foreach($notmailed_posts as $pid=>$post)
		{
			
			 if ($row = $DB->get_record('trans_forum_posts', array('id'=>$post->id))) 
			 {
			   /*todo 
			   * Has this message already been moderated? */
			 }
			 else
			 {
				//print var_dump($post);
				/*."'".sql_escape($post->subject)."', "
					."'".sql_escape($post->message)."', "
					.$post->format.", "
					."'".sql_escape($post->attachment)."', "
					 Revisar */
			/*Agregando el foro a la tabla intermedia para traducir */		 
			$pst = new stdClass();
			$pst->id		    = $post->id;
			$pst->discussion    = $post->discussion;
			$pst->parent        = $post->parent;
			$pst->userid        = $post->userid;
			$pst->created       = $post->created;
			$pst->modified      = $post->modified;
			$pst->mailed        = $post->mailed;
			$pst->subject       = $post->subject;
			$pst->message       = $post->message;
			$pst->messageformat = $post->messageformat;
			$pst->attachments   = isset($post->attachments) ? $post->attachments : null;
			$pst->totalscore    = $post->totalscore;
			$pst->mailnow       = $post->mailnow;
			$pst->moderated     = 0;
				
			$idinserted = $DB->insert_record('trans_forum_posts', $pst, true);
			
			/*Modifica el id automático que agrega la inserción anterior por el correcto del post original */
			$sql = "update {trans_forum_posts} set id = $post->id  where id =  $idinserted ";
			$DB->execute($sql);
						 	
	        /* Se modifica el foro para que no sea visible mientras esté sin moderar */
		   	$pst = new stdClass();
			$pst->id		    = $post->id;
			$pst->subject       = '(Subject hidden until confirmation)';
			$pst->message		= 'This message will be moderated soon, please be patient.';
			$pst->mailed		= 42;
						
			$DB->update_record('forum_posts', $pst, false);
    		
    		if (forum_moderateplease_email($post))
    		 { 
				 print "Enviado email de Moderación";
		     }
     		 print 'line 203 ';			
		   }	
			
		 
		}
	return; 
}
/*
 * Esta función envía un mensaje a los moderadores de la lista
 * indicando que hay un mensaje nuevo y pueden moderarlo desde ahi
 * */
function forum_moderateplease_email($post)
{
	Global $CFG, $DB;
	
	
	$queryparams = array('mod/forum:editanypost');
	list($insql, $inparams) = $DB->get_in_or_equal(array(1, 3, 4));
	$params = array_merge($queryparams, $inparams);
	
	$sql= "SELECT username, email, firstname, lastname 
			FROM {role_assignments} 
			LEFT JOIN {user}
				ON role_assignments.userid=user.id  
			LEFT JOIN {role_capabilities} ON
				role_assignments.roleid = role_capabilities.roleid
			WHERE capability = ? AND permission IN (1, 3, 4)  ;";

	  
	  $moderators = $DB->get_records_sql($sql, $queryparams);
	  
	  foreach($moderators as $moderatorid=>$moderator)
	  {
	   	$authors = $DB->get_records_sql("SELECT username, email, firstname, lastname, userid,picture  FROM {forum_posts} LEFT JOIN {user}
                  ON {forum_posts}.userid={user}.id WHERE {forum_posts}.id=$post->id  " );
       
		foreach($authors as $authorid=>$author)
		{
			$courses      = array();
			$courseid     = $post->course;	
			$courses = $DB->get_record('course', array('id'=>$courseid)); //Se busca el nombre de la lista (course)
            $site = get_site(); //Se llama el nombre del sitio
			$recipient=$moderator->firstname." ".$moderator->lastname." <".$moderator->email.">";
			$msg_subject='New forum post has arrived, please moderate';
			$msg_headers='From: Moodle <dontreply@localhost.localdomain>
MIME-Version: 1.0
Content-Type: text/html;
  charset="utf-8"
Content-Transfer-Encoding: 8bit';
		$msg_message='<html><head></head><body style="font-size:11pt;font-family:Verdana, sans-serif;">
<p>The following message has arrived to the forum:</p>

Send by: <b> <a href="'.$CFG->wwwroot.'/user/view.php?id='.$author->userid.'">'.$author->firstname .' '. $author->lastname.'</a> </b></p>
<p><b>Lista:</b> ['. $site->shortname . '-' . $courses->shortname. ']'.'</p>
<p><b>'.$post->subject.'</b></p>
<p>'.$post->message.'</p>
<hr>
<p>To edit the post as a moderator, please <a href="'.$CFG->wwwroot.'/mod/forum/post.php?edit='.$post->id.'">click here</a>.</p>
</body></html>';
		mail($recipient, $msg_subject, $msg_message, $msg_headers);
		}
      }
}

function sql_escape($string)
{
	$string=preg_replace('/\'/', "''", $string);
	return $string;
}

/** translation of a forum post to their respective language
 *
 */
function forum_translate_post($postid)
{
	Global $CFG, $DB;
	# First a dummy implementation, to get the other things straight first.
	
	# Get the forum id of the post.
	$post=$DB->get_record('forum_posts', array('id'=>$postid));
	
	$forum=$DB->get_records_sql(
		"SELECT {forum}.id 
		FROM {forum_posts}
		LEFT JOIN {forum_discussions}
			ON {forum_posts}.discussion={forum_discussions}.id
		LEFT JOIN {forum}
			ON {forum_discussions}.forum={forum}.id 
		WHERE 
			{forum_posts}.id=$postid");

	foreach($forum as $fid=>$forum_row)
	{
		$forum_id=$forum_row->id;
	}
	# Lookup the table "forum_translate_to", to see which action has
	# to be taken ( if any).
	$query=
		"SELECT count(*) as c 
		FROM {forum_translate_to} 
		WHERE 
			forum_id_orig=$forum_id;";
	
	$row = $DB->get_record_sql($query);
	
	/* * Are there any translations to be done?
	*	print("There are ".$row['c']." translations to be done for forum id $forum_id.<br>\n"); 
	*/ 
	
	if($row->c == 0)
	{
		return;
	}

	/** Here continues the case for translations.
	 * */
	$query=
		"SELECT * 
		FROM {forum_translate_to} 
		WHERE 
			forum_id_orig=$forum_id;";
		
	$sth = $DB->get_records_sql($query);
	$post=$DB->get_records_sql("SELECT * FROM {forum_posts} WHERE id=$postid");
	foreach($post as $postid=>$message)
	{
		# No operation, we just want to get all message properties.
	}
	
	foreach($sth as $row)
	{
		if($message->parent==0)
		{
			/* We have a new forum topic, the topic itself also needs to
			* be translated, then created on the other list, and then a
			* translated post can be stored within that new topic.
			* */

			/* Get the original discussion attributes
			 * */
			$discussion= $DB->get_records_sql(
			"SELECT {forum_discussions}.* 
			FROM {forum_posts} 
			LEFT JOIN {forum_discussions} 
				ON
					{forum_posts}.discussion={forum_discussions}.id 
			WHERE
				{forum_posts}.id=$postid");
			
			foreach($discussion as $discussionid=>$discussion_data)
			{
				// No operation, just getting the attributes.
			}
           
            /* Subject no translation
			* #$translateddiscussion_name=babelfishtranslate($discussion_data->name, $row['lang_iso2']);
			* */
			$translateddiscussion_name=$discussion_data->name;
            // $original_discussion_id = $discussion_data->id;
			$translated_course= $DB->get_records_sql(
				"SELECT DISTINCT course
				FROM {forum} 
				WHERE id=".$row->forum_id_translated);
			
			foreach($translated_course as $d_id=>$f_course)
			{}

			$translated_discussion= new stdClass();
			$translated_discussion->name=sql_escape($translateddiscussion_name);
			$translated_discussion->course=$f_course->course;
			$translated_discussion->forum=$row->forum_id_translated;
			$translated_discussion->firstpost=0;
			$translated_discussion->userid=$discussion_data->userid;
			$translated_discussion->groupid=$discussion_data->groupid;
			$translated_discussion->assessed=$discussion_data->assessed;
			$translated_discussion->timemodified=$discussion_data->timemodified;
			$translated_discussion->usermodified=$discussion_data->usermodified;
			$translated_discussion->timestart=$discussion_data->timestart;
			$translated_discussion->timeend=$discussion_data->timeend;

			/* Insert that data as the new translated forum. */
			$discussion_id= $DB->insert_record("forum_discussions", $translated_discussion);
			/* The "firstpost" data is wrong, this has to be updated
			* after actually putting the first post there.
			* */
			$firstpost_update_necessary=1;

			$parent_id=0;
		}
		else
		{
			/* Getting of the translated message
			* discussion ID and parent message ID.
			* Using the table "forum_post_id_translated" in the additional
			* moderation database, to get the translated parent attributes,
			* which are used for the current translated post as well.
			* */
			$query=
				"SELECT target_id 
				FROM {forum_post_id_translated}
				WHERE 
					orig_id=".$message->parent." AND
					lang_iso2='".$row->lang_iso2."';";
					
			$row2=$DB->get_record_sql($query);
			
			$translated_parent_id=$row2->target_id;
			/* We have the translated parent post ID, so now we gather its attributes.*/
			$trans_parent_post= $DB->get_records_sql("SELECT * FROM {forum_posts} WHERE id=".$translated_parent_id);
			foreach($trans_parent_post as $tpp_id=>$tpp)
			{}
			
			$discussion_id=$tpp->discussion;
			$parent_id=$translated_parent_id;
		}
		
		$translated_message=new stdClass();
		$translated_message->discussion=$discussion_id;
		$translated_message->parent=$parent_id;
		$translated_message->userid=$message->userid;
		$translated_message->created=$message->created;
		$translated_message->modified=$message->modified;
		$translated_message->mailed=0;
		/* $translated_message->subject=sql_escape(babelfishtranslate($message->subject,$row['lang_iso2']));
         * Subject without translation */
		$translated_message->subject=sql_escape($message->subject);

		/* Add the Subject for translation to the body */
		$que= "SELECT discussion FROM {forum_posts} WHERE id=".$postid;
		
		$row22=$DB->get_record_sql($que);

		$original_discussion_id=$row22->discussion;

		$subjectword_translation['es']='---> Este mensaje ha sido traducido por un programa sin revisión. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank">  Mensaje original. </a><br><br> TRADUCCIÓN DEL TEMA </b> ';
		$subjectword_translation['en']='---> This message was translated by program without revision. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Original message. </a><br><br> TRANSLATED SUBJECT  </b>';
		$subjectword_translation['fr']='---> Ce message a été traduit par programme sans révision. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Original message . </a> <br><br> TRADUCTION DU SUJET </b> ';
		$subjectword_translation['ht']='---> Sa a te tradui pa mesaj pwogram san yo pa revizyon. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Original message . </a> <br><br> PWOBLÈM CREOLE </b> ';
		$subjectword_translation['ar']='---> وقد ترجمت هذه الرسالة عن طريق البرنامج دون تنقيح.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> رسالة الأصلي. </a> <br><br> ترجم هذا الموضوع </b> ';
		$subjectword_translation['sq']='---> Ky mesazh është përkthyer nga programi pa rishikim.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Mesazhi origjinal. </a> <br><br> Përkthyer temë </b> ';
		$subjectword_translation['af']='---> Hierdie boodskap is vertaal deur die program sonder hersiening. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Oorspronklike boodskap.  </a> <br><br> VERTAAL ONDERWERP </b> ';
		$subjectword_translation['be']='---> Гэта паведамленне было пераведзена праграму без зменаў. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Першапачатковае паведамленне. </a> <br><br> Пераклад тэмы </b> ';
		$subjectword_translation['bg']='---> Това съобщение е преведена от програмата, без преразглеждане.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Оригиналното съобщение.  </a> <br><br> Преведено предмет </b> ';
		$subjectword_translation['ca']='---> Aquest missatge va ser traduït pel programa sense revisió.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Missatge original.  </a> <br><br> Traduït tema </b> ';
		$subjectword_translation['zh']='---> 此消息是未经修改翻译程序。 <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> 原始消息。</a> <br><br> 翻译主体 </b> ';
		$subjectword_translation['zh-CN']='---> 此消息是未经修改翻译程序。 <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> 原始消息。 </a> <br><br> 翻译主体 </b> ';
		$subjectword_translation['zh-TW']='---> 此消息是未經修改翻譯的程序。 <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> 原始消息。 </a> <br><br> 翻譯的問題 </b> ';
		$subjectword_translation['hr']='---> Ova poruka je preveden od programa bez revizije.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Izvorni poruku.  </a> <br><br> Prevedeno temu </b> ';
		$subjectword_translation['cs']='---> Tato zpráva byla přeložena do programu, bez revize.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Původní zprávu.  </a> <br><br> Překládal předmětu </b> ';
		$subjectword_translation['da']='---> Dette indlæg blev oversat af programmet uden revision. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Oprindelige meddelelse.  </a> <br><br> Oversat emne </b> ';
		$subjectword_translation['nl']='---> Dit bericht werd vertaald door het programma zonder herziening. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Oorspronkelijke bericht.  </a> <br><br> Vertaald onderwerp </b> ';
		$subjectword_translation['et']='---> See sõnum on tõlkinud programmi ilma vaadata. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Algne sõnum. </a> <br><br> Tõlgitud teema </b> ';
		$subjectword_translation['tl']='---> Ang mensaheng ito ay isinalin sa pamamagitan ng programa nang walang pagbabago.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Orihinal na mensahe.  </a> <br><br> Mga isinaling paksa </b> ';
		$subjectword_translation['fi']='---> Tämä viesti on käännetty ohjelma ilman tarkistusta. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Alkuperäinen viesti. </a> <br><br> Käännetty aihe </b> ';
		$subjectword_translation['gl']='---> Esta mensaxe foi traducido por programa sen revisión. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Mensaxe orixinal.  </a> <br><br> Traducido asunto </b> ';
		$subjectword_translation['de']='---> Diese Nachricht wurde durch das Programm ohne Revision übersetzt. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Original-Meldung.  </a> <br><br> Übersetzt Thema </b> ';
		$subjectword_translation['el']='---> Το μήνυμα αυτό μεταφράστηκε από το πρόγραμμα χωρίς αναθεώρηση. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Αρχικό μήνυμα. </a> <br><br> Μεταφρασμένα θέμα </b> ';
		$subjectword_translation['iw']='---> הודעה זו תורגמה על ידי התוכנית ללא רוויזיה. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> הודעה מקורית.  </a> <br><br> תורגם על הנושא. </b> ';
		$subjectword_translation['hi']='---> यह संदेश प्रोग्राम के द्वारा संशोधन के बिना अनुवाद किया गया था. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> मूल संदेश. </a> <br><br> विषय अनूदित </b> ';
		$subjectword_translation['hu']='---> Ezt az üzenetet fordította program nélkül felülvizsgálatát. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Eredeti üzenet. </a> <br><br> Fordította téma </b> ';
		$subjectword_translation['is']='---> Þessi skilaboð voru þýðing eftir áætlun án þess að endurskoðun. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Upphaflegu skilaboðin. </a> <br><br> Þýtt efni. </b> ';
		$subjectword_translation['id']='---> Pesan ini diterjemahkan oleh program tanpa revisi. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Original message.  </a> <br><br> Diterjemahkan subjek </b> ';
		$subjectword_translation['ga']='---> Aistríodh an teachtaireacht seo de réir cláir gan athbhreithniú. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Teachtaireacht bunaidh. </a> <br><br> Aistrithe ábhar </b> ';
		$subjectword_translation['it']='---> Questo messaggio è stato tradotto da programma senza revisione. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Messaggio originale. </a> <br><br> Tradotto soggetto. </b> ';
		$subjectword_translation['ja']='---> このメッセージは、改訂することなくプログラムによって翻訳された。 <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> 元のメッセージ。 </a> <br><br> 翻訳対象 </b> ';
		$subjectword_translation['ko']='---> 이 메시지는 월요일 수정없이 프로그램에 의해 번역되었다. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> 원본 메시지가 나타납니다. </a> <br><br> 제목을 번역했습니다 </b> ';
		$subjectword_translation['lv']='---> Šo ziņu tulkoja programma bez pārskatīšanu. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Sākotnējo ziņojumu. </a> <br><br> Tulkots tēmu </b> ';
		$subjectword_translation['lt']='---> Ši žinutė buvo išversta programa be persvarstymo. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Pirminį pranešimą.  </a> <br><br> Išversta objektas </b> ';
		$subjectword_translation['mk']='---> Оваа порака беше преведени од страна на програмата без ревизија. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Оригиналната порака. </a> <br><br> Превод тема </b> ';
		$subjectword_translation['ms']='---> Mesej ini telah diterjemahkan oleh program tanpa semakan. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Mesej asal. </a> <br><br> Diterjemahkan tertakluk </b> ';
		$subjectword_translation['mt']='---> Dan il-messaġġ ġie tradott bil-programm mingħajr reviżjoni.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Oriġinali messaġġ.  </a> <br><br> Tradotti suġġett </b> ';
		$subjectword_translation['no']='---> Denne meldingen ble oversatt av programmet uten revisjon. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Opprinnelige meldingen. </a> <br><br> Oversatt emne </b> ';
		$subjectword_translation['fa']='---> این پیام توسط برنامه بدون ویرایشهای ترجمه شده است. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> پیام اصلی است. </a> <br><br> ترجمه موضوع </b> ';
		$subjectword_translation['pl']='---> Wiadomość została przetłumaczona przez program bez zmian. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Oryginalna wiadomość. </a> <br><br> Przetłumaczone temat </b> ';
		$subjectword_translation['pt']='---> Esta mensagem foi traduzido por programa sem revisão. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Mensagem original. </a> <br><br> Traduzido assunto </b> ';
		$subjectword_translation['ro']='---> Acest mesaj a fost tradus de către programul fără revizuire. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Mesajul original. </a> <br><br> Tradus subiect </b> ';
		$subjectword_translation['ru']='---> Это сообщение было переведено программу без изменений. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Первоначальное сообщение. </a> <br><br> Перевод темы </b> ';
		$subjectword_translation['sr']='---> Ова порука је преведен од стране програма без ревизије. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Оригиналне поруке. </a> <br><br> Преведено предмет </b> ';
		$subjectword_translation['sk']='---> Táto správa bola preložená do programu, bez revízie. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Pôvodnú správu.  </a> <br><br> Prekladal predmetu </b> ';
		$subjectword_translation['sl']='---> To sporočilo je bilo prevedno od programa brez revizije. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Original sporočilo. </a> <br><br> Prevedeno predmet </b> ';
		$subjectword_translation['sw']='---> Ujumbe huu Tafsiri kwa mpango bila marekebisho. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Nakala ujumbe. </a> <br><br> Maelezo ya somo </b> ';
		$subjectword_translation['sv']='---> Detta budskap har översatts av programmet utan översyn. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Ursprungliga meddelandet. </a> <br><br> Översatt ämne. </b> ';
		$subjectword_translation['th']='---> ข้อความนี้ถูกแปลโดยโปรแกรมโดยไม่ต้องแก้ไข <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> ข้อความต้นฉบับ  </a> <br><br> เรื่องแปล </b> ';
		$subjectword_translation['tr']='---> Bu mesaj revizyon programı ile tercüme edilmiştir. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Özgün ileti. </a> <br><br> Konusu çeviri </b> ';
		$subjectword_translation['uk']='---> Це повідомлення було переведено програму без змін. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Первісне повідомлення. </a> <br><br> Переклад теми </b> ';
		$subjectword_translation['vi']='---> Thông báo này đã được dịch bởi chương trình mà không sửa đổi. <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Gốc tin nhắn. </a> <br><br> Dịch chủ đề </b> ';
		$subjectword_translation['cy']='---> Mae`r neges yn cyfieithu gan rhaglen heb adolygu.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> Neges wreiddiol. </a> <br><br> Cyfieithwyd pwnc </b> ';
		$subjectword_translation['yi']='---> דעם אָנזאָג איז געווען איבערגעזעצט דורך פּראָגראַם אָן רעוויזיע.  <a  href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$original_discussion_id.'#p'.$postid.'" target="_blank"> אָריגינעל אָנזאָג.</a> <br><br> איבערזעצונג ונטערטעניק </b> ';
 
	//$original_lang = substr(LanguageOf($message->message),0,2);
	//Reconocimiento del idioma del mensaje por Google. Ahora sin textcat
	$original_lang= idioma($message->message, ""); //Please add Google API
	
        if ($original_lang =="sp") 
        {
			$original_lang ="es";
        }
       	if ($original_lang =="po") 
       	{
			$original_lang ="pt";
        }

        if ($original_lang !=  $row->lang_iso2) 
        {
			$translated_message->message= $message->subject."</b><br><br>\n".sql_escape($message->message);
			//$translated_message->message=sql_escape(babelfishtranslate($translated_message->message,$row['lang_iso2']));
			//$translated_message->message="<b>".$subjectword_translation[$row['lang_iso2']].": ". $translated_message->message;
			//$translated_message->message=sql_escape(googletranslate($translated_message->message,$row->lang_iso2));
			if ($row->api == "Google"){
				$translated_message->message=sql_escape(googleTranslate($translated_message->message,$row->lang_iso2));
				//Si falla usamos el otro traductor
				if (empty($translated_message->message)) {
					$translated_message->message=sql_escape(microsoftTranslate($translated_message->message,$row->lang_iso2));
				}	
					
			}else {
				//Si falla usamos el otro traductor disponible
				$translated_message->message=sql_escape(microsoftTranslate($translated_message->message,$row->lang_iso2));
				if (empty($translated_message->message)) {
					$translated_message->message=sql_escape(googleTranslate($translated_message->message,$row->lang_iso2));
				}	
		
			}			
			
			$translated_message->message="<b>".$subjectword_translation[$row->lang_iso2] . ": ". $translated_message->message;
        } 
		else
		{
			$translated_message->message= sql_escape($message->message);
        }  
                 
        $translated_message->messageformat=$message->messageformat;
		$translated_message->attachment=$message->attachment;
		$translated_message->totalscore=$message->totalscore;
		$translated_message->mailnow=$message->mailnow;

		/* Checking the case of forum parameters:
		* 	"single_moderation=1": No moderation of translation necessary
		*$sql="SELECT count(id) as c from trans_forum_parameter WHERE forum_id=".$row['forum_id_translated']." AND varname='single_moderation' AND val=1;";
		* */

		$sql="SELECT count(id) as c from {forum_translate_to} WHERE forum_id_translated=".$row->forum_id_translated." AND single_moderation = 1;";
		
		$single_moderation_check =$DB->get_record_sql($sql);
		$new_post_id= $DB->insert_record("forum_posts", $translated_message);
		$sql = "Select singlemoderation from {trans_forum_posts} where id = $postid";
		$single = $DB->get_record_sql($sql);
		
		if(($single_moderation_check->c > 0) && ($single->singlemoderation > 0))
		{
			$sql="INSERT INTO {trans_forum_posts} (id, moderated) VALUES ($new_post_id, 1);";
			
			$translated_mess = $translated_message; 
			$translated_mess->id =$new_post_id;
			$translated_mess->moderated =1;
			$inserted = $DB->insert_record("trans_forum_posts", $translated_mess, true);
			
			/*Modifica el id automático que agrega la inserción anterior por el correcto del post original */
			$sql = "update {trans_forum_posts} set id = $new_post_id  where id =  $inserted ";
			$DB->execute($sql);
		} else {
			
			//mysql_query($sql);
			//a revisar
			$translated_mess = $translated_message; 
			$translated_mess->id =$new_post_id;
			$translated_mess->moderated = 0;
			//$translated_mess->singlemoderation = 0;
			
			$inserted = $DB->insert_record("trans_forum_posts", $translated_mess, true);
									
			/*Modifica el id automático que agrega la inserción anterior por el correcto del post original */
			$sql = "update {trans_forum_posts} set id = $new_post_id  where id =  $inserted ";
			$DB->execute($sql);
			
			/* Se modifica el foro para que no sea visible mientras esté sin moderar */
		   	$pst = new stdClass();
			$pst->id		    = $new_post_id;
			$pst->subject       = '(Subject hidden until confirmation)';
			$pst->message		= 'This message will be moderated soon, please be patient.';
			$pst->mailed		= 42;
						
			$DB->update_record('forum_posts', $pst, false);
						
		}	
		

		$translated_m=new stdClass();
		$translated_m->orig_id=$postid;
		$translated_m->lang_iso2=$row->lang_iso2;
		$translated_m->target_id=$new_post_id;
		
		$inserted2 = $DB->insert_record("forum_post_id_translated", $translated_m, true);
			
		if(!empty($firstpost_update_necessary) and $firstpost_update_necessary ==1)
		{
			$sql="UPDATE {forum_discussions} SET firstpost=$new_post_id WHERE id=$discussion_id";
			$DB->execute($sql);
			$firstpost_update_necessary=0;
		}
	}
}

function googletranslate($html, $TargetLanguage)
/***
 * Pre:  $html, cadena que contiene el mensaje a traducir proveniente del área de texto del forol
 *       $TargetLanguage, idioma destino de la traducción
 * 
 * Post: $strVal, cadena que contiene la traducción realizada por el google api
 *****/
{
	$strVal = ejecutaTraductor($TargetLanguage, $html, ""); //Please add Google API
	
	return htmlspecialchars_decode($strVal);
}

function microsoftTranslate($html, $TargetLanguage)
/***
 * Pre:  $html, cadena que contiene el mensaje a traducir proveniente del área de texto del forol
 *       $TargetLanguage, idioma destino de la traducción
 * 
 * Post: $strVal, cadena que contiene la traducción realizada por el api de Microsoft
 *****/
{
	$translator = new MicrosoftTranslator(ACCOUNT_KEY);
	
	$translator->translate('', $TargetLanguage,$html);
	$traduccion = $translator->response->jsonResponse;
	$strVal = json_decode(utf8_encode($traduccion));
	$strVal = $strVal->translation;
	
	$strVal =  htmlspecialchars_decode($strVal);
	
    return $strVal;

}


function LanguageOf($htmltext)
{
	Global $tradlang2iso;

	$tradlang2iso['english']="en";
	$tradlang2iso['french']="fr";
	$tradlang2iso['spanish']="es";
	$tradlang2iso['portuguese']="pt";
	$tradlang2iso['haitian_creole']="ht";


	$plaintext=Html2RoughPlaintext($htmltext);
	
	$tmpfile=tempnam("/tmp", "LanguageOf");
	$fh=fopen($tmpfile, "w");
	fwrite($fh, $plaintext);
	fclose($fh);

	$lang=`text_cat $tmpfile`;
	$lang=preg_replace('/\s*$/s', "", $lang);
	unlink($tmpfile);
      	print "lang";
	print $lang."\n";
	return $lang;
	return $lang;
}

function Html2RoughPlaintext($html)
{
	$text = utf8_decode($html);
	$text = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $text);
	$text = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $text);
	$text=preg_replace('/<[^>]*>/', "", $text);
	$text=preg_replace('/[\[\]\\()|<>\*\/\?\!\'"\.]/', " ", $text);
	return $text;
}

function htmlupload($html)
{
	/* To make sure, that the encoding is well-defined, we have to
	* provide a HTML header with UTF-8 encoding, and a proper footer.
	* */
	$header='<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title></title>
</head>
<body>
';
	$footer='</body></html>';

	$html=$header.$html.$footer;
	$ch = curl_init('http://tradauto.colnodo.apc.org/tradauto.php');
	//$ch = curl_init('http://aulaenlinea.net/aula/tra/tradauto.php');

	
	curl_setopt($ch, CURLOPT_POST      ,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS    ,'event=upload&'.'html='.urlencode($html));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
	curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
	curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
	$Rec_Data = curl_exec($ch);

	/* Getting the dynamically created URL */
	preg_match('/<!-- start URL -->\s*(.*)\s*<!-- end URL -->/', $Rec_Data, $matches);
	return $matches[1];
}

/* First translating an english test URL */
function babelfishtranslate($html, $TargetLanguage)
{
	Global $tradlang2iso;

	/* Lines commented out by double dashes "##" are just disabling the
	* real translation during the testing phase. */

	$url=htmlupload($html);

#	print "The URL of the content is <a href=\"$url\">$url</a><br>";
	$SourceLanguage= $tradlang2iso[LanguageOf($html)];
	if($SourceLanguage == $TargetLanguage)
	{
		return $html;
	}
	## This return is only for testing, disable it for the real
	## Babelfish translation.
##	return $SourceLanguage."_".$TargetLanguage." ".$html;

#	$SourceLanguage= $tradlang2iso[LanguageOf($html)];
#	# Creating a public temporary web page with the message.
#	$rootdir="/var/www/moodle/";
#	$urlroot="http://home.plumeyer.org/moodle/";
#	$transdir="trans/";
#
#	# Write the HTML into a publicly available file.
#	$tmpfile=tempnam($rootdir.$transdir, "index");
#	$fh=fopen($tmpfile, "w");
#	fwrite($fh, $html);
#	fclose($fh);
#	$origpage=basename($tmpfile).".html";
#	rename($tmpfile, $rootdir.$transdir.$origpage);
#	sleep(2);
	# Preparing Cookie file
	$ckfile = tempnam ("/tmp", "CURLCOOKIE");

# $ch = curl_init('http://babelfish.yahoo.com/');
# curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile); 
# curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
# curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
# curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
# $Rec_Data = curl_exec($ch);
# preg_match_all('/<form[^>]*>/i', $Rec_Data, $matches);
# var_dump($matches);
# foreach($matches[0] as $i)
# {
# 	if(preg_match('/translate_url/', $i, $matches_form))
#	{
#		preg_match('/action\s*=\s*"([^"]*)"/i', $i, $matches_form);
#		$Form_URL=$matches_form[1];
#	}
#	
# }
# print "<b>".$Form_URL."</b><br>\n";
#// I would like to see something like this in the form action:
#// http://us.lrd.yahoo.com/_ylt=A0LEUFl48p1J328B5CSu7s4F/SIG=11snm03g9/EXP=1235174392/*-http%3A//babelfish.yahoo.com/translate_url
# $ch = curl_init($Form_URL
 $ch = curl_init("http://babelfish.yahoo.com/translate_url"
 	."?" 	
	.'doit=done&'
	.'tt=url&'
	.'intl=1&'
	.'fr=bf-home&'
	.'trurl='.urlencode($url)."&"
	."lp=".$SourceLanguage."_".$TargetLanguage

 );
 curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile); 
 curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
 curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
 curl_setopt($ch, CURLOPT_HEADER      ,0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
 $Rec_Data = curl_exec($ch);
// }
 	$frameset=$Rec_Data;
//	print $frameset."\n\n";
//         <frame name="BabelFishBody" SRC="http://babelfish.yahoo.com/translate_url_load?lp=en_fr&trurl=http%3A%2F%2Fhome.plumeyer.org%2Fmoodle%2Ftrans%2FindexDv1wgk&sig=iw_d928mhP9kqsfNGD.G1g--" scrolling="Yes" frameboder="yes" bordercolor="#93B2DD">
	$TranslationFrame= preg_match('/frame name="BabelFishBody" SRC="([^"]*)"/s', $frameset, $matches);
#	var_dump($matches);
	$TranslationFrame=$matches[1];

	sleep(5);
	$ch=curl_init($TranslationFrame);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
 curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile); 
 curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
//	curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
 curl_setopt($ch, CURLOPT_HEADER      ,0);
	$Rec_Data = curl_exec($ch);
	
#	print $Rec_Data;

#URL=http://66.163.168.225/babelfish/translate_url_content?.intl=us&lp=en_fr&trurl=http%3A%2F%2Fhome.plumeyer.org%2Fmoodle%2Ftrans%2Findex20a8zM.html"/> 
	preg_match('/URL=([^"]*)"/s', $Rec_Data, $matches);
	$TranslationURL=$matches[1];
#	print "<b>".$TranslationURL."</b>\n";

	$ch=curl_init($TranslationURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
 curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile); 
 curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
 curl_setopt($ch, CURLOPT_HEADER      ,0);
	$Rec_Data = curl_exec($ch);
	# I don't want to see the REFRESH now, turning "<>" to "&lt;&gt;" to prepare the next step.
#	$Rec_Data=preg_replace('/</', "&lt;", $Rec_Data);
#	$Rec_Data=preg_replace('/>/', "&gt;", $Rec_Data);
#Working in Lynx:
#http://66.196.80.202/babelfish/translate_url_content?.intl=us&lp=de_en&trurl=http%3A%2F%2Fplumeyer.org
#http://66.196.80.202/babelfish/translate_url_content?.intl=us&lp=en_fr&trurl=http%3A%2F%2Fhome.plumeyer.org%2Fmoodle%2Ftrans%2FindexvKTh8x.html
#http://babelfish.yahoo.com/translate_url?lp=en_fr&trurl=http%3A%2F%2Fhome.plumeyer.org%2Fmoodle%2Ftrans%2FindexvKTh8x.html&fr=blogrd
	# Catching the error, which occurs because of very short texts,
	# which not permit the language recognizer to have a chance for
	# its stochastics to really work.
	# The Babelfish error looks like this:
	#  "...Error encountered while translating text..."
	# With some HTML added, in several lines.
	if(preg_match('/Error encountered while translating text/', $Rec_Data))
	{
		return "(Language recognition error) ".$html;
	}
	return babelfish_html_cleanup($Rec_Data);
}

function babelfish_html_cleanup($html)
{
	# We don't need the HTML header until <body..>
	$html=preg_replace('/^.*<body[^>]*>/is', "", $html);
	# Cutting away HTML comments
	$html=preg_replace('/<!--.+?-->/is', "", $html);
	# Cutting away scripts like Javascript together with their
	# "noscript" counterparts.
	$html=preg_replace('/<script.+?<\/script>/is', "", $html);
	$html=preg_replace('/<noscript.+?<\/noscript>/is', "", $html);
	# Cutting away footer (</body></html>)
	$html=preg_replace('/\s*<\/body>\s*<\/html>/is', "", $html);
	# Cleaning up links (is there more than href ones?)
	# This works only with double quoted links (XHTML conform)
	#    example:  <a href="this/is/a/link">
	preg_match_all('/href\s*=\s*"([^"]*)"/', $html, $matches);
	
	#var_dump($matches);
	foreach($matches[1] as $link)
	{
	#	print "$link\n";
		preg_match('/trurl=([^"]*)$/', $link, $urlmatch);
		$origlink=$urlmatch[1];
		$origlink=urldecode($origlink);
	#	print "Original link: $origlink\n";
		# Replacing Babelfish links by the original ones.
		# If there should be links, which use http://<raw Babelfish IP>/translate_url/...
		# , which should be extremely rare, then they will break and
		# as well link to the original page.
		$pattern=preg_quote($link);
		$pattern=preg_replace('/\//', "\/", $pattern);
	#	print "\nPATTERN: ".$pattern."\n";
		$pattern='/"'.$pattern.'"/';
		$html=preg_replace($pattern, $origlink, $html);
	}
		
	return $html;
}

?>