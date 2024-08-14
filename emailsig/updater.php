<?php
function emailsig_pre_set_site_transient_update_plugins($transient) {
  // The current version of your plugin
  $emailsig_current_version = '1.1';
  // GitHub repository information
  $repository = 'bodfather/emailsig';
  $token = ''; // Replace with your personal access token

  // Check for updates from the GitHub repository
  $update = emailsig_check_for_updates($emailsig_current_version, $repository, $token);

  if ($update) {
      // Update is available
      $transient->response['emailsig/emailsig.php'] = (object) $update;
  } else {
      // No update is available, populate no_update field
      $item = (object) array(
          'id'            => 'emailsig/emailsig.php',
          'slug'          => 'emailsig',
          'plugin'        => 'emailsig/emailsig.php',
          'new_version'   => $emailsig_current_version,
          'url'           => '',
          'package'       => '',
          'icons'         => array(),
          'banners'       => array(),
          'banners_rtl'   => array(),
          'tested'        => '', // Optionally add tested WP version
          'requires_php'  => '', // Optionally add required PHP version
          'compatibility' => new stdClass(),
      );
      $transient->no_update['emailsig/emailsig.php'] = $item;
  }

  return $transient;
}

add_filter('pre_set_site_transient_update_plugins', 'emailsig_pre_set_site_transient_update_plugins');

function emailsig_check_for_updates($emailsig_current_version, $repository, $token) {
  // Make the API request to GitHub
  $response = wp_remote_get("https://api.github.com/repos/$repository/releases/latest", array(
      'headers' => array(
          'Authorization' => "token $token",
          'Accept'        => 'application/vnd.github.v3+json',
      )
  ));

  if (is_wp_error($response)) {
      return false;
  }

  $release = json_decode(wp_remote_retrieve_body($response));

  if (isset($release->tag_name) && version_compare($emailsig_current_version, $release->tag_name, '<')) {
      return array(
          'id'            => 'emailsig/emailsig.php',
          'slug'          => 'emailsig',
          'plugin'        => 'emailsig/emailsig.php',
          'new_version'   => $release->tag_name,
          'url'           => $release->html_url,
          'package'       => isset($release->zipball_url) ? $release->zipball_url : '',
          'icons'         => array(), // Add icon URLs if you have them
          'banners'       => array(), // Add banner URLs if you have them
          'banners_rtl'   => array(), // Add RTL banner URLs if applicable
          'tested'        => '6.0', // Optionally add the latest WP version tested with
          'requires_php'  => '7.0', // Optionally add the minimum PHP version required
          'compatibility' => new stdClass(),
      );
  }

  return false;
}
