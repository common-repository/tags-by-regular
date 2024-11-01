<?php
/*
Plugin Name: Tags by regular expressions
Plugin URI: http://askj.ru/wp-plugins/tags-by-regular
Version: 0.1
Tags: tags
Author: <a href="http://askj.ru/">Oleg Bugrimov</a>
Description: If posting does not have Tags and/or Title, plugin generates it using regular expressions defined by admin
*/

/*
Copyright 2008 Oleg Bugrimov (bugrimov@gmail.com)

Simple Auto Tags is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program.  If not, see <http://www.gnu.org/licenses/>.
*/

$TBR_post_content = '';

function TBR_set_tags($post_id, $post) {
	$post_content = $post->post_content;
	if( $post_content != '' && ! wp_get_post_tags($post_id) ){
	  echo sprintf("post: %s", print_r($post, 1));
	  $regulars = get_option('TBR_tags_regulars');
	  if ( !is_array($regulars) ){
		$regulars = array();
	  }
      $tags = array();
	  foreach($regulars as $tag_re => $re_re){
		echo "tag: $tag_re";
		if( preg_match($re_re, $post_content))
		  $tags[] = $tag_re;
	  }
	  wp_add_post_tags($post_id, $tags);
	}
	return $post_id;

}

function TBR_control() {

	$options = get_option('TBR_tags_regulars');
	$title_regulars_arr = get_option('TBR_tags_title_regulars');
	if ( !is_array($options) ){
		$title_regulars_arr = array();
	}
	if ( !is_array($title_regulars_arr) ){
		$title_regulars_arr = array();
	}
	if ( $_POST['TBR-control-submit'] ) {
		$options = array();
		$title_regulars_arr = split("\n", strip_tags(stripslashes($_POST['TBR_tags_title_regulars'])));
		$title_regulars_arr = array_map('trim', $title_regulars_arr);
		$title_regulars_arr = array_filter( $title_regulars_arr);
		if ( !is_array($title_regulars_arr) ){
		  $title_regulars_arr = array();
	    }
		$tags_regulars = strip_tags(stripslashes($_POST['TBR_tags_regulars']));
		$tlines = split("\n", $tags_regulars);
		foreach( $tlines as $tline ){
			list($tag, $reg) = split("/", $tline);
			$tag = trim($tag);
			$reg = trim($reg);
			if($tag !== '' && $reg !== '' )
			  $options[$tag] = "/$reg/";
		}
		update_option('TBR_tags_regulars', $options);
		update_option('TBR_tags_title_regulars', $title_regulars_arr);
		?><div id='message' class='updated fade'><p><strong>Tags by regulars list updated!</strong></p></div><?php
	}

	$textarea = "";
	foreach( $options as $tag => $reg){
		$textarea .= sprintf("%s %s\n", $tag, $reg);
	}
	
?>
<div class="wrap">
<h2>Tags by regular</h2>
	<form id="TBR-tags-by-regular" name="TBR-tags-by-regular" method="post" action="options-general.php?page=tags-by-regular.php">
	<h3>Tags and regular expressions</h3>
	<p>This pairs are <i>TAG /REGULAR_EXPR/</i>. For example:
	<pre>
	conference /conferenc/
	cars /car|jeep|hummer/
	</pre></p>
	<textarea rows="6" cols="100" name="TBR_tags_regulars" id="TBR_tags_regulars"><?php echo($textarea); ?></textarea>
	<br>
	<br>
	<p>
	Also, you can auto generate title, and clear it from a first words. Like 'Hi!' or 'Hello people!'
	<br>
	One phrase per line, like:
	<pre>
	Hi
	Hello people
	</pre>
	</p>
	<textarea rows="6" cols="100" name="TBR_tags_title_regulars" id="TBR_tags_title_regulars"><?php echo(join("\n", $title_regulars_arr)); ?></textarea>
	<br>
	
	<input type="submit" name="submit" id="submit" value="submit" />
	<input type="hidden" id="TBR-control-submit" name="TBR-control-submit" value="1" />
	</form>
</div>
<?php

}

function TBR_add_menu() {
	add_options_page('Tags by regular', 'Tags by regular', 8, basename(__FILE__),'TBR_control');
}

function TBR_save_post_content($content = ''){
	global $TBR_post_content;
	$TBR_post_content = $content;
	//die($TBR_post_content);
	return $content;
}

function TBR_save_post_title($title = ''){
	global $TBR_post_content;
	if( $TBR_post_content != '' && ($title == '' || preg_match("/^\d\d\-\d\d\-\d\d.*/", $title))){
	  $regulars_arr = get_option('TBR_tags_title_regulars');
	  $regulars_arr = array_filter($regulars_arr);
	  $regulars = join("|", $regulars_arr);
	  $cleared_content = preg_replace("/^\s*($regulars)\s*/ui", "", $TBR_post_content);
	  $cleared_content = preg_replace("/^\s*[!\?\.\,:]*\s*/", "", $cleared_content);

      if( preg_match("/^(.{80}[^\s]*)/", $cleared_content, $regs) ){
        $title = ucfirst(trim($regs[1]));
       }
 	}
	return $title;
}

add_action('save_post', 'TBR_set_tags',10, 2);
add_action('admin_menu', 'TBR_add_menu');

add_action('content_save_pre', 'TBR_save_post_content');
add_action('title_save_pre', 'TBR_save_post_title');

?>