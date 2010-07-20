<?php
/*
Plugin Name: Wordpress MU Theme Stats
Plugin URI: http://wpmututorials.com/
Description: Adds submenu to see theme stats, shows themes by user and most popular themes.
Version: 2.8
Author: Ron Rennick (original plugin contributions by Phillip Studinski)
Author URI: http://ronandandrea.com/
 
*/
/* Copyright:	(C) 2009 Ron Rennick, All rights reserved.  

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
function ra_theme_stats_add_page() {
	// Add a submenu
	if(is_site_admin()) {
		add_submenu_page('wpmu-admin.php', 'Theme Stats', 'Theme Stats', 0, basename(__FILE__), 'ra_theme_stats_page');
		add_action('admin_head','ra_add_show_hide_css');
	}
}
add_action('admin_menu', 'ra_theme_stats_add_page');

function ra_theme_stats_page() { 
	if( !is_site_admin() ) {
		die( 'You don\'t have permissions to use this page.' );
	} ?>
<div class=wrap>
      <h2>Theme Statistics</h2>
<?php
	global $wpdb, $current_site;
	$blogs  = $wpdb->get_results("SELECT blog_id, domain, path FROM $wpdb->blogs " .
		"WHERE site_id = {$current_site->id} ORDER BY domain ASC");
	$blogtheme = array();
	if ($blogs) {
		foreach ($blogs as $blog) {
			$blogOptionsTable  = "{$wpdb->base_prefix}{$blog->blog_id}_options";
			$blogtemplate = $wpdb->get_var("SELECT option_value FROM $blogOptionsTable WHERE option_name = 'template'");
			if($blogtemplate)
				$blogtheme[$blog->blog_id] = $blogtemplate;
		}
	}
	$themeinfo = array();
	$themeblogs = array();
	// do stats
	if($blogs) {
		foreach ($blogs as $blog) {
			if( !array_key_exists( $blogtheme[$blog->blog_id], $themeinfo ) ) {
				$themeinfo[$blogtheme[$blog->blog_id]] = 1;
				$themeblogs[$blogtheme[$blog->blog_id]] = array();
				$themeblogs[$blogtheme[$blog->blog_id]][0] = $blog;
			} else {
				$themeblogs[$blogtheme[$blog->blog_id]][$themeinfo[$blogtheme[$blog->blog_id]]] = $blog;
				$themeinfo[$blogtheme[$blog->blog_id]]++;
			}
		}
	}
	arsort($themeinfo);
	// show stats
	echo '<ul>';
	foreach( $themeinfo as $themename => $themecount ) {
		echo '<li>';
		ra_show_hide_begin($themename, "$themename ($themecount)", '','ul'); ?>
<?php
		foreach($themeblogs[$themename] as $bloginfo) {
			$url = "http://" . $bloginfo->domain . $bloginfo->path;
			if($bloginfo->path == '/') {
				$domain = explode('.',$bloginfo->domain);
				$blogname = $domain[0];
			} else {
				$blogname = substr($bloginfo->path, 1, -1);
			}
			echo '<li><a href="' . $url . '">' . $blogname .  '</a> - <a href ="' . $url . "wp-admin/" . '">Backend</a></li>';
		} ?>
<?php
		ra_show_hide_end('ul');
		echo '<br /></li>';
	} ?>
</ul>
<script type="text/javascript"><!--
	function ra_show(id, newclass)
	{
	  var el = document.getElementById(id);
	  if(el) {
	    if(newclass) {
		if(el.className==newclass) el.className="ra-hide"; 
		else el.className=newclass;
	    } else {
		if(el.className=="") el.className="ra-hide";
		else el.className="";
	    }
	  }
	}
//--></script>
</div><?php
}

function ra_add_show_hide_css() { ?>
<style type="text/css">.ra-hide { display:none; }</style> 
<?php }

function ra_show_hide_begin($HTMLid, $linktext = 'Expand/Collapse', $CSSclass = '', $tag = 'div') {
	$q = "'";
	if($HTMLid && $linktext) {
		echo '<a href="javascript:void(0)" onclick="ra_show('.$q.$HTMLid.$q.','.
		$q.$CSSclass.$q.')">'.$linktext.'</a>';
		echo '<'.$tag.' id="'.$HTMLid.'" class="ra-hide">';
	}
}
function ra_show_hide_end($tag = 'div') { 
	echo '</'.$tag.'>';
}
?>