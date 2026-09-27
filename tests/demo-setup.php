<?php
/** Transactional importer regression checks; run only against a disposable/local database. */
defined('ABSPATH') || exit;
global $wpdb;
$assert = static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$wpdb->query('START TRANSACTION');
try {
 update_option('kn_settings',array_merge(kn_defaults(),['contact_email'=>'owner@example.com','hero_prefix'=>'Keep my introduction']));
 delete_option('knt_demo_setup_applied');
 update_option('show_on_front','posts');update_option('page_on_front',0);update_option('page_for_posts',0);
 $about=get_page_by_path('about');$content=$about->post_content;
 $first=knt_import_demo();
 $assert(!isset($first['error']),'Import must complete');
 $assert('owner@example.com'===get_option('kn_settings')['contact_email'],'Importer must preserve contact recipients');
 $assert('Keep my introduction'===get_option('kn_settings')['hero_prefix'],'Importer must preserve customized settings');
 $assert('page'===get_option('show_on_front'),'Import must configure a static Home page');
 $home=(int)get_option('page_on_front');$writing=(int)get_option('page_for_posts');
 $assert($home&&$writing&&$home!==$writing,'Home and Writing must be assigned separately');
 $assert('publish'===get_post_status($home)&&'publish'===get_post_status($writing),'Assigned pages must be published');
 $assert($content===get_post_field('post_content',$about->ID),'Existing biography must survive');
 $assert(isset($first['pages_created'],$first['pages_existing']),'Page results must be reported');
 $second=knt_import_demo();
 $assert(0===$second['created']&&0===$second['pages_created'],'Repeating an import must not duplicate posts or pages');
 $assert($home===(int)get_option('page_on_front')&&$writing===(int)get_option('page_for_posts'),'Repeat import must reuse pages');
 update_option('show_on_front','posts');
 knt_import_demo(false);
 $assert('posts'===get_option('show_on_front'),'Opting out must preserve Reading settings');
 wp_update_post(['ID'=>$home,'post_status'=>'draft']);update_option('page_on_front',0);
 $blocked=knt_demo_add_pages();
 $assert(isset($blocked['error']),'An unpublished page collision must report failure');
 $assert('draft'===get_post_status($home),'Importer must not publish existing private work');
 foreach(get_posts(['post_type'=>'page','post_status'=>['publish','draft'],'numberposts'=>-1]) as $page){
  if(in_array($page->post_name,['home','writing','about','contact'],true))wp_update_post(['ID'=>$page->ID,'post_name'=>'kn-test-preserved-'.$page->ID]);
 }
 update_option('page_on_front',0);update_option('page_for_posts',0);
 $fresh=knt_demo_add_pages();
 $assert(!isset($fresh['error'])&&4===$fresh['created'],'A fresh site must get all four pages');
 echo "Demo setup: pages, static assignments, settings preservation, reporting, and repeat import passed.\n";
} finally {$wpdb->query('ROLLBACK');wp_cache_flush();}
