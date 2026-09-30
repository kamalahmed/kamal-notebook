<?php
/** Isolated local upgrade test. No providers contacted or email sent. */
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException('Local only.'); }
if ( ! function_exists('kn_migrate_contact_credentials') ) { throw new RuntimeException('Credential migration is missing.'); }
$names=array('kn_settings','knt_contact_turnstile_secret','wpcf7','kn_contact_credentials_v2');$saved=array();foreach($names as $name){$saved[$name]=get_option($name,null);}
$assert=static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
try {
 update_option('kn_settings',array('contact_captcha'=>'turnstile','contact_captcha_source'=>'existing','hero_prefix'=>'Keep this'));delete_option('knt_contact_turnstile_secret');delete_option('kn_contact_credentials_v2');update_option('wpcf7',array('turnstile'=>array('legacy-public-key-12345'=>'legacy-secret-key-12345')));
 kn_migrate_contact_credentials();$s=get_option('kn_settings');
 $assert('turnstile'===$s['contact_captcha'],'Migration cannot disable selected protection.');
 $assert('Keep this'===$s['hero_prefix'],'Unrelated preferences preserved.');
 $assert('legacy-public-key-12345'===$s['contact_turnstile_sitekey'],'Saved public key carried into theme.');
 $assert('legacy-secret-key-12345'===get_option('knt_contact_turnstile_secret'),'Saved private key carried into theme.');
 delete_option('wpcf7');$assert('legacy-secret-key-12345'===knt_contact_captcha_credentials()['secret'],'Theme must work after obsolete option is removed.');
 $s['contact_captcha_source']='custom';$s['contact_turnstile_sitekey']='direct-key-12345';update_option('kn_settings',$s);update_option('knt_contact_turnstile_secret','direct-secret-12345');delete_option('kn_contact_credentials_v2');update_option('wpcf7',array('turnstile'=>array('other-key-12345'=>'other-secret-12345')));kn_migrate_contact_credentials();
 $assert('direct-key-12345'===get_option('kn_settings')['contact_turnstile_sitekey']&&'direct-secret-12345'===get_option('knt_contact_turnstile_secret'),'Direct keys must never be overwritten.');
 $s=get_option('kn_settings');$s['contact_captcha_source']='existing';update_option('kn_settings',$s);delete_option('kn_contact_credentials_v2');kn_migrate_contact_credentials();
 $assert('other-key-12345'===get_option('kn_settings')['contact_turnstile_sitekey']&&'other-secret-12345'===get_option('knt_contact_turnstile_secret'),'Selected existing credentials must replace inactive custom keys.');
 update_option('kn_settings',array('contact_captcha'=>'turnstile','contact_captcha_source'=>'existing'));delete_option('knt_contact_turnstile_secret');delete_option('kn_contact_credentials_v2');delete_option('wpcf7');kn_migrate_contact_credentials();
 $assert('turnstile'===get_option('kn_settings')['contact_captcha'],'Missing legacy keys must not silently disable verification.');
 echo "Standalone upgrade preserves credentials, settings and selected protection; runtime works without legacy storage.\n";
} finally {foreach($saved as $name=>$value){if(null===$value)delete_option($name);else update_option($name,$value);}}
