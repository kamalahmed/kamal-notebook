<?php
$file = dirname(__DIR__) . '/tutorials/contact-form/notebook-contact-form/notebook-contact-form.php';
if(!file_exists($file))throw new RuntimeException('The contact tutorial plugin is missing.');
require_once $file;
$sent=0;
add_filter('pre_wp_mail',function($return,$atts)use(&$sent){$sent++;return true;},10,2);
$nonce=wp_create_nonce('kncf_contact');
$valid=['kncf_nonce'=>$nonce,'contact_name'=>'Tutorial Test','contact_email'=>'reader@example.com','contact_message'=>'A question about the tutorial.','website'=>''];
delete_transient(kncf_rate_key());
try {
 foreach ([['kncf_nonce'=>'wrong'],['contact_name'=>''],['contact_email'=>"bad\r\nBcc:other@example.com"],['contact_message'=>['bad']],['contact_message'=>str_repeat('x',5001)]] as $change) {
  if(kncf_process(array_replace($valid,$change))==='sent')throw new RuntimeException('Invalid form accepted.');
 }
 if($sent!==0)throw new RuntimeException('Invalid data attempted mail delivery.');
 if(kncf_process($valid)!=='sent'||$sent!==1)throw new RuntimeException('Valid form did not reach intercepted mail.');
 if(kncf_process($valid)!=='limited'||$sent!==1)throw new RuntimeException('Repeat submission was not limited.');
 delete_transient(kncf_rate_key());
 add_filter('pre_wp_mail',fn()=>false,20);
 if(kncf_process($valid)!=='failed')throw new RuntimeException('Mail failure was reported as success.');
 echo "Contact tutorial: invalid nonce, missing/array/oversized inputs, header injection, valid submission, rate limit and mail failure passed. No email sent.\n";
} finally {delete_transient(kncf_rate_key());}
