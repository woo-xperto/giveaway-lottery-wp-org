<?php
require_once 'wxgiveaway_setting_page.php';
// Dashboard Left side menu 
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

  }
}

