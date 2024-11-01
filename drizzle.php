<?php

/**

 * @package Simple paywall, membership, lead generation and grow traffic platform (by Drizzle)

 */

/*

Plugin Name: Simple paywall, membership, lead generation and grow traffic platform (by Drizzle)

Plugin URI: https://getdrizzle.com/

Description: Simple plugin for premium content creators and publishers to sell premium content and grow traffic in the most user-friendly way.

Version: 1.3.0

Author: Drizzle

Author URI: https://getdrizzle.com/

License: GPLv2 or later

Text Domain: simple-paywall

*/



/*

This program is free software; you can redistribute it and/or

modify it under the terms of the GNU General Public License

as published by the Free Software Foundation; either version 2

of the License, or (at your option) any later version.



This program is distributed in the hope that it will be useful,

but WITHOUT ANY WARRANTY; without even the implied warranty of

MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

GNU General Public License for more details.



You should have received a copy of the GNU General Public License

along with this program; if not, write to the Free Software

Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/



// Make sure we don't expose any info if called directly

if ( !function_exists( 'add_action' ) ) {

  echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';

  exit;

}



define( 'DRIZZLE_VERSION', '1.3.0' );

define( 'DRIZZLE__MINIMUM_WP_VERSION', '4.0' );

define( 'DRIZZLE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );



register_activation_hook( __FILE__, array( 'Drizzle_Admin', 'plugin_activation' ) );

register_deactivation_hook( __FILE__, array( 'Drizzle_Admin', 'plugin_deactivation' ) );



require_once( DRIZZLE__PLUGIN_DIR . 'class.drizzle.php' );



add_action( 'init', array( 'Drizzle', 'init' ) );



if ( is_admin() ) {

  require_once( DRIZZLE__PLUGIN_DIR . 'class.drizzle-admin.php' );

  add_action( 'init', array( 'Drizzle_Admin', 'init' ) );

}

