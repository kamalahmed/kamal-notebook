<?php
/** Notebook settings: progressive enhancement keeps every control available. */
defined( 'ABSPATH' ) || exit;
function kn_render_settings(): void {
 if ( ! current_user_can( 'manage_options' ) ) { return; }
 $tabs = array( 'home' => 'Homepage', 'reading' => 'Reading', 'contact' => 'Contact & security', 'about' => 'About' );
 ?>
 <div class="wrap kn-admin" data-kn-settings>
  <header class="kn-settings-header"><div><span class="kn-settings-kicker">THE NOTEBOOK / SITE SETTINGS</span><h1><?php esc_html_e( 'Make it yours.', 'kamal-notebook' ); ?></h1><p>Choose what leads the homepage, how people read, and how they reach you.</p></div><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">View your site ↗</a></header>
  <?php settings_errors(); ?>
  <div class="kn-settings-search"><label for="kn-settings-search">Find a setting</label><div><input type="search" id="kn-settings-search" placeholder="Try “featured”, “CAPTCHA”, or “email”…" autocomplete="off"><button type="button" data-clear-search>Clear</button></div><p data-search-status role="status" aria-live="polite"></p></div>
  <form method="post" action="options.php">
   <?php settings_fields( 'kn_settings' ); ?>
   <div class="kn-settings-layout">
    <nav class="kn-settings-tabs" role="tablist" aria-label="Settings categories">
     <?php foreach ( $tabs as $id => $label ) : ?><button type="button" role="tab" id="kn-tab-<?php echo esc_attr($id); ?>" aria-controls="kn-panel-<?php echo esc_attr($id); ?>" aria-selected="<?php echo 'home'===$id?'true':'false'; ?>" data-tab="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><span aria-hidden="true">↗</span></button><?php endforeach; ?>
     <p>Your changes are saved together, including settings in the other tabs.</p>
    </nav>
    <div class="kn-settings-panels">
     <section id="kn-panel-home" role="tabpanel" aria-labelledby="kn-tab-home" data-panel="home">
      <div class="kn-panel-intro"><span>01 / THE FIRST IMPRESSION</span><h2>Homepage</h2><p>Give readers a place to start. Choose a single story or a small collection.</p></div>
      <?php if ( function_exists('knt_featured_settings_fields') ) { knt_featured_settings_fields(); } else { ?><p>Activate Notebook Tools to choose featured articles.</p><?php } ?>
      <div class="kn-setting-card" data-kn-setting><h3>Home introduction</h3><p>The headline and introduction next to your featured articles.</p><?php kn_setting_field('hero_prefix','Headline'); kn_setting_field('hero_accent','Accented word or phrase'); kn_setting_field('hero_deck','Introduction',true); ?></div>
     </section>
     <section id="kn-panel-reading" role="tabpanel" aria-labelledby="kn-tab-reading" data-panel="reading">
      <div class="kn-panel-intro"><span>02 / MADE FOR READING</span><h2>Reading</h2><p>Set the archive layout and the tools available to your readers.</p></div>
      <div class="kn-setting-card" data-kn-setting><h3>Blog listing</h3><div class="kn-layout-choices">
       <?php foreach(array('grid'=>['Illustrated grid','Let the covers introduce each story.'],'index'=>['Numbered index','A compact list for browsing the archive.']) as $value=>$copy): ?>
       <label><input type="radio" name="kn_settings[archive_layout]" value="<?php echo esc_attr($value); ?>" <?php checked(kn_option('archive_layout'),$value); ?>><span class="kn-layout-preview kn-preview-<?php echo esc_attr($value); ?>"><i></i><i></i><i></i></span><strong><?php echo esc_html($copy[0]); ?></strong><small><?php echo esc_html($copy[1]); ?></small></label>
       <?php endforeach; ?>
      </div></div>
      <div class="kn-setting-card" data-kn-setting><h3>Reader tools</h3><p>Saved stories stay in the reader’s browser. Feedback requires Notebook Tools.</p><?php kn_setting_checkbox('save_enabled','Save stories in the reader’s browser'); kn_setting_checkbox('share_enabled','Share via device menu or copy link'); kn_setting_checkbox('feedback_enabled','Private “Was this useful?” feedback'); ?></div>
     </section>
     <section id="kn-panel-contact" role="tabpanel" aria-labelledby="kn-tab-contact" data-panel="contact">
      <div class="kn-panel-intro"><span>03 / AN OPEN, PROTECTED LINE</span><h2>Contact &amp; security</h2><p>Control where messages go and protect the form from unwanted submissions.</p></div>
      <div class="kn-setting-card" data-kn-setting><h3>Message delivery</h3><p>Separate multiple recipient addresses with commas. Delivery uses your WordPress mail configuration.</p><?php kn_setting_field('contact_email','Send messages to'); ?></div>
      <?php if(function_exists('knt_security_settings_fields')) { knt_security_settings_fields(); } else { ?><p>Activate Notebook Tools to manage contact protection.</p><?php } ?>
     </section>
     <section id="kn-panel-about" role="tabpanel" aria-labelledby="kn-tab-about" data-panel="about">
      <div class="kn-panel-intro"><span>04 / THE PERSONAL PART</span><h2>About</h2><p>The short note at the bottom of the homepage.</p></div>
      <div class="kn-setting-card" data-kn-setting><h3>About band</h3><?php kn_setting_field('about_heading','Heading'); kn_setting_field('about_text','Short description',true); ?><p>Your full biography is edited on the <a href="<?php $page=get_page_by_path('about');echo esc_url($page?get_edit_post_link($page->ID):admin_url('edit.php?post_type=page')); ?>">About page</a>.</p></div>
     </section>
     <p class="kn-no-results" data-no-results hidden>No settings match that search. Try a shorter phrase or clear the search.</p>
     <div class="kn-settings-save"><?php submit_button('Save notebook settings','primary','submit',false); ?><span>Applies to all tabs.</span></div>
    </div>
   </div>
  </form>
  <footer class="kn-admin-help"><h2>Ready to write?</h2><p>Use Heading 2 for the article outline, Notebook Code for copyable examples, and Featured image for the cover. You can also mark an article as featured in the post editor.</p><a class="button" href="<?php echo esc_url(admin_url('post-new.php')); ?>">Write a new post ↗</a></footer>
 </div>
 <?php
}
