<?php

// * Miscellaneous settings

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

   
//   $ADMIN->add('experimental', $temp);
   $ADMIN->add('experimental', new admin_externalpage('translation', get_string('forumstranslation','admin'),  '/admin/translation/forums.php'));
   $ADMIN->add('experimental', new admin_externalpage('resend', get_string('resendforum','admin'),  '/admin/translation/resend.php'));



 
} 
