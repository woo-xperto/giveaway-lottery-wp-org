<?php
require_once 'wxgiveaway_setting_page.php';
// === >>>> Dashboard Left side menu <<<< === \\
if(!function_exists('wxgiveaway_wp_admin_dashboard_menu_reg_pro')){
  add_action("admin_menu", "wxgiveaway_wp_admin_dashboard_menu_reg");
  function wxgiveaway_wp_admin_dashboard_menu_reg() {
      add_menu_page(
        __('Giveaways system','giveaway-lottery'), 
        __('Giveaways','giveaway-lottery'), // menu title
        'manage_options', // capability
        'giveaway-system', // sluge
        'wxgiveaway_setting_page', // function for page
        'dashicons-tickets-alt',
        10
      );
      add_submenu_page(
        'giveaway-system', // parent menu slug
        __('Giveaways Settings','giveaway-lottery'), // Page title
        __('Setting','giveaway-lottery'), // Menu title
        'manage_options',  // Capability
        'setting', // sub menu slug
        'wxgiveaway_setting_page' // sub meun funciton for page
      );
      // Add the first submenu item with a different title
      /*add_submenu_page(
        'giveaway-system', // parent slug
        __('Overview','giveaway-lottery'), // page title
        __('Overview','giveaway-lottery'), // submenu title
        'manage_options', // capability
        'giveaway-system', // slug for submenu
        'wxgiveaway_overview_page' // function for submenu page
      );

      //add submenu 2
      add_submenu_page(
        'giveaway-system', // parent menu slug
        __('Giveaways','giveaway-lottery'), // Page title
        'Giveaways', // Menu title
        'manage_options',  // Capability
        'giveaways', // sub menu slug
        'wxgiveaway_giveaway_page' // sub meun funciton for page
      );
      //add submenu 3
      add_submenu_page(
        'giveaway-system', // parent menu slug
        __('Winners','giveaway-lottery'), // Page title
        'Winners', // Menu title
        'manage_options',  // Capability
        'winners', // sub menu slug
        'wxgiveaway_winners_page' // sub meun funciton for page
      );
      //add submenu 4
      add_submenu_page(
        'giveaway-system', // parent menu slug
        __('Report','giveaway-lottery'), // Page title
        'Report', // Menu title
        'manage_options',  // Capability
        'report', // sub menu slug
        'wxgiveaway_report_page' // sub meun funciton for page
      );*/
      //add submenu 5
      
      /*add_submenu_page(
        'giveaway-system', // parent menu slug
        __('Giveaway system license','giveaway-lottery'), // Page title
        'License', // Menu title
        'manage_options',  // Capability
        'wxg-license', // sub menu slug
        'wxgiveaway_license_page' // sub meun funciton for page
      );*/

  }
}

