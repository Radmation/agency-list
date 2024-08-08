<?php

class AgencyList
{
  /**
   * Constructor.
   */
  public function __construct() {
    // Actions
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  /**
   * Enqueues our scripts and stylesheets.
   */
  public function enqueue_scripts() {
    $theme_version = wp_get_theme()->get('Version');

    // Js Wizard
    wp_enqueue_style('wizardjs-css', 'https://cdn.jsdelivr.net/gh/AdrianVillamayor/Wizard-JS@1.9.9/styles/css/main.css', false);
    wp_enqueue_script('wizardjs-js', 'https://cdn.jsdelivr.net/gh/AdrianVillamayor/Wizard-JS@1.9.9/src/wizard.min.js', false);

    // Agency list
    wp_enqueue_style('agenylist-css', get_theme_file_uri('assets/dist/main.css'), array('wizardjs-css'), $theme_version);
    wp_enqueue_script('agencylist-js', get_theme_file_uri('assets/dist/main.bundle.js'), array('wizardjs-js'), $theme_version, true);
  }
}

$agencyList = new AgencyList();