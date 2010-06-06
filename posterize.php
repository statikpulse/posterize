<?php
/*
Plugin Name: Posterize
Plugin URI: http://statikpulse.com/posterize
Description: This plugin will automatically cross-post your Wordpress blog entry to your Posterous site. 
Version: 2.1.0
Author: Yan Sarazin 
Author URI: http://statikpulse.com
*/

/*  Copyright 2010  Yan Sarazin  (email : yan@statikpulse.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* When plugin is activated */
register_activation_hook(__FILE__,'activatePosterize');
register_deactivation_hook( __FILE__, 'deactivatePosterize' );

function activatePosterize() {
   add_option("posterous_email", '', '', 'yes');
   add_option("posterous_password", '', '', 'yes');
   add_option("post_type", '', '', 'yes');
}

function deactivatePosterize() {
   delete_option('posterous_email');
   delete_option('posterous_password');
   delete_option('post_type');
}

if(is_admin()){
   
   add_action('admin_menu', 'posterizeAdminMenu');
   add_filter('plugin_row_meta', 'posterizePluginLinks',10,2);
   function posterizeAdminMenu(){
      add_options_page('Posterize Settings', 'Posterize', 'administrator', 'posterize-settings', 'posterizeAdminPage');
   }
   
   function posterizePluginLinks($links, $file){
      if( $file == 'posterize/posterize.php') {
         $links[] = '<a href="' . admin_url( 'options-general.php?page=posterize-settings' ) . '">' . __('Settings') . '</a>';
         $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QC745TKR6AHBS" target="_blank">Donate</a>';
      }
      return $links;
      
   }

   function posterizeAdminPage(){
      ?>
      <script type="text/javascript" charset="utf-8" src="<?php echo WP_PLUGIN_URL;?>/posterize/js/jquery-1.4.min.js"></script>
      <script type="text/javascript" charset="utf-8">
         var plugin_url = '<?php echo WP_PLUGIN_URL;?>/posterize/';
      </script>
      <script type="text/javascript" charset="utf-8" src="<?php echo WP_PLUGIN_URL;?>/posterize/js/posterize.js"></script>
      <link rel="stylesheet" href="<?php echo WP_PLUGIN_URL;?>/posterize/css/styles.css" type="text/css" media="screen" title="no title" charset="utf-8" />
      
      <form method="post" action="options.php" id="posterize_settings_form" name="posterize_settings_form">      
      <?php wp_nonce_field('update-options'); ?>
      <h1>Posterize Settings</h1>
      <table>
         <tr>
            <td colspan="2"><h3>Posterous Login Information</h3></td>
         </tr>
         <tr>
            <td width="100"><label for="posterous_email">Email:</label></td>
            <td><input name="posterous_email" type="text" id="posterous_email" value="<?php echo get_option('posterous_email'); ?>" /></td>
         </tr>
         <tr>
            <td><label for="posterous_password">Password:</label></td>
            <td><input name="posterous_password" type="password" id="posterous_password" value="<?php echo get_option('posterous_password'); ?>" /></td>
         </tr>
         <tr>
            <td colspan="2"><h3>Posterous Site</h3></td>
         </tr>
         <tr>
            <td width="100"><label for="posterous_site">Site ID:</label></td>
            <td><input name="posterous_site" type="text" id="posterous_site" value="<?php echo get_option('posterous_site'); ?>" /> <a href="#" onClick="Posterize.get_sites();">Get Sites</a></td>
         </tr>    
         <tr>
            <td colspan="2" id="sites" style="display: none;"></td>  
         <tr>
            <td colspan="2"><h3>What to post</h3></td>
         </tr>
         <tr>
            <td colspan="2"><input type='radio' id="post_type" name="post_type" value="1" <?php if(get_option('post_type')=="1"){ echo "checked='checked'";} ?>> Link back to post</td>
         </tr>
         <tr>
            <td colspan="2"><input type="radio" id="post_type" name="post_type" value="2" <?php if(get_option('post_type')=="2"){ echo "checked='checked'";} ?>> Post full content</td>
         </tr>
         <tr>
            <td colspan="2">
               <input type="hidden" name="action" value="update" />
               <input type="hidden" name="page_options" value="posterous_email,posterous_password,post_type,posterous_site" />
               <input type="submit" value="<?php _e('Save Changes') ?>" />
            </td>
         </tr>
      </table>
      </form>
      <?php
   }
   
}

function posterous_email(){
   echo get_option('posterous_email');
}


function posterous_password(){
   echo get_option('posterous_password');
}


function send_to_posterous($post_ID)  {
   if(get_option('posterous_email')!='' && get_option('posterous_password')!=''){
      global $userdata;
      get_currentuserinfo();

      $post = get_post($post_ID);
      $title = urlencode($post->post_title);
	  $tags = array();
	  $posttags = get_the_tags($post_ID);
	  if ($posttags) {
		foreach($posttags as $tag) {
			$tags[] = $tag->name; 
		}
	  }
      if(get_option('post_type')=="2"){
         $body = urlencode(nl2br($post->post_content));
      }else{
         $body = urlencode('<a href="'.get_permalink($post_ID).'">'.$post->post_title.'</a>');
      }
	  $source = urlencode('Posterize');
	  $sourceLink = urlencode('http://statikpulse.com/posterize');


      $ch = curl_init(); 
      curl_setopt($ch, CURLOPT_URL, 'http://posterous.com/api/newpost?source='.$source.'&sourceLink='.$sourceLink.'&site_id='.get_option('posterous_site').'&title='.$title.'&body='.$body.'&tags='.implode(',', $tags)); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
      curl_setopt($ch, CURLOPT_USERPWD, "".get_option('posterous_email').":".get_option('posterous_password')."") ;

      $data = curl_exec($ch); 
      curl_close($ch);
   }
}

add_action('draft_to_publish', 'send_to_posterous');
add_action('pending_to_publish', 'send_to_posterous');
?>
