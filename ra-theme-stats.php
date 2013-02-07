<?php
/*
Plugin Name: Multisite Theme Stats
Plugin URI: http://wpmututorials.com/
Description: Adds submenu to see theme stats, shows themes by user and most popular themes.
Version: 2.8.3
Author: Ron Rennick, RavanH
Author URI: http://wpmututorials.com/
Network: true

(original plugin contributions by Phillip Studinski)
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
class RA_Theme_Stats {
	function __construct() {
		if( function_exists( 'is_network_admin' ) )
			add_action( 'network_admin_menu', array( $this, 'add_network_page' ) );
		else
			add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'plugins_loaded', array( $this, 'localization' ) );
	}
	
	function RA_Theme_Stats() {
		$this->__construct();		
	}

	function localization() {
		if ( is_admin() )
			load_plugin_textdomain('ra-theme-stats', false, dirname( plugin_basename( __FILE__ ) ) );
	}
		
	function add_page() {
		if( is_super_admin() ) {
			add_submenu_page('ms-admin.php', __('Theme Statistics','ra-theme-stats'), __('Theme Statistics','ra-theme-stats'), 'manage_network_themes', 'ra_theme_stats', array( $this, 'admin_page' ) );
			if( isset( $_GET['page'] ) && $_GET['page'] == 'ra_theme_stats' )
				add_action( 'admin_head', array( $this, 'show_hide_css' ) );
		}
	}
	function add_network_page() {
		add_submenu_page('themes.php', __('Theme Statistics','ra-theme-stats'), __('Theme Statistics','ra-theme-stats'), 'manage_network_themes', 'ra_theme_stats', array( $this, 'admin_page' ) );
		if( isset( $_GET['page'] ) && $_GET['page'] == 'ra_theme_stats' )
			add_action( 'admin_head', array( $this, 'show_hide_css' ) );
	}

	function admin_page() {
		if( !current_user_can( 'manage_network_themes' ) ) {
			wp_die( 'You don\'t have permissions to use this page.' );
		} 

		$activethemes = array();
		$themeinfo = array();
		$themeblogs = array();
		?>
	<div class=wrap>
	      <h2><?php _e('Theme Statistics','ra-theme-stats'); ?></h2>
	<?php
		global $wpdb, $current_site;
		$blogs  = $wpdb->get_results("SELECT blog_id, domain, path FROM $wpdb->blogs " .
			"WHERE site_id = {$current_site->id} ORDER BY domain ASC");
		$blogtheme = array();
		if ($blogs) {
			foreach ($blogs as $blog) {
				if( ( $blogtemplate = get_blog_option( $blog->blog_id, 'stylesheet' ) ) )
					$blogtheme[$blog->blog_id] = $blogtemplate;
			}
		}
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
		// show active theme stats
		echo '
		<h3>'.__("Active Themes","ra-theme-stats").' ('.count($themeinfo).')</h3>
		<p><em>'.__("Click the count to display the sites using a given theme.","ra-theme-stats").'</em></p>
		<ul>';
		foreach( $themeinfo as $themeslug => $themecount ) {
			echo '<li>';
			$thistheme = wp_get_theme($themeslug);
			$activethemes[] = $thistheme;
			$this->show_hide_begin($thistheme, '('.$themecount.' '._n("activation", "activations", $themecount,"ra-theme-stats").')', '','ul'); ?>
	<?php
			foreach($themeblogs[$themeslug] as $bloginfo) {
				$url = "http://" . $bloginfo->domain . $bloginfo->path;
				if($bloginfo->path == '/') {
					$domain = explode('.',$bloginfo->domain);
					$blogname = $domain[0];
				} else {
					$blogname = substr($bloginfo->path, 1, -1);
				}
				echo '<li style="margin-left: 4em;">' . get_blog_option( $bloginfo->blog_id, 'blogname', $blogname ) . ' - <a href="' . $url . '">'.__("Visit").'</a> | <a href ="' . $url . 'wp-admin/' . '">'.__("Dashboard").'</a></li>';
			} ?>
	<?php
			$this->show_hide_end('ul');
			echo '</li>';
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
		</div>
		<?php
		// Get a list of all the themes
		$themes = wp_get_themes();
		$inactivethemes = array_diff($themes,$activethemes);
		// show inactive themes
		echo '
		<h3>'.__("Inactive Themes","ra-theme-stats").' ('.count($inactivethemes).')</h3>
		<ul>';
		foreach( $inactivethemes as $theme ) {
			//if ($theme->)
			echo '<li>'.$theme.'</li>';
		}
		echo '
		</ul>';

		echo '
		<h3>'.__("Other Statistics","ra-theme-stats").'</h3>
		<ul>';

		// Get a list of network themes
		$themes = wp_get_themes(array('allowed' => 'network'));
		// show themes
		echo '
		<li>';
		$this->show_hide_begin(__("Network Allowed Themes","ra-theme-stats"), '('.count($themes).')', '','ul');
		foreach( $themes as $theme ) {
			echo '<li style="margin-left: 4em;">'.$theme.'</li>';
		}
		$this->show_hide_end('ul');
		echo '
		</li>';

		// Get a list of themes with errors
		$themes = wp_get_themes(array('errors' => true));
		// show themes
		echo '
		<li>';
		$this->show_hide_begin(__("Themes with errors","ra-theme-stats"), '('.count($themes).')', '','ul');
		foreach( $themes as $theme ) {
			echo '<li style="margin-left: 4em;">'.$theme.'</li>';
		}
		$this->show_hide_end('ul');
		echo '
		</li>
		</ul>';

	}

	function show_hide_css() { ?>
	<style type="text/css">.ra-hide { display:none; }</style>
	<?php }

	function show_hide_begin($HTMLid, $linktext, $CSSclass = '', $tag = 'div') {
		$q = "'";
		if ($linktext=='')
			$linktext = __('Expand/Collapse','ra-theme-stats');
		if($HTMLid) {
			echo $HTMLid.' <a href="javascript:void(0)" onclick="ra_show('.$q.$HTMLid.$q.','.
			$q.$CSSclass.$q.')" title="'.__('Expand/Collapse','ra-theme-stats').'">'.$linktext.'</a>';
			echo '<'.$tag.' id="'.$HTMLid.'" class="ra-hide">';
		}
	}
	function show_hide_end($tag = 'div') {
		echo '</'.$tag.'>';
	}
}
$ra_theme_stats = new RA_Theme_Stats();
