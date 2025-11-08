<?php
/**
 * TRM Plugin Updater
 * Checks for updates from GitHub and shows update notifications
 */

class TRM_Updater {
    
    private $github_repo = 'ravishankarsingh-ux/trm-counseling-scheduler';
    private $github_raw_url = 'https://raw.githubusercontent.com/ravishankarsingh-ux/trm-counseling-scheduler/main';
    private $plugin_file = 'trm-counseling-scheduler/trm-counseling-scheduler.php';
    private $current_version;
    private $transient_key = 'trm_update_check';
    private $cache_duration = 43200; // 12 hours

    public function __construct() {
        $this->current_version = TRM_COUNSELING_VERSION;
        
        // Check for updates
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        
        // Add update schedule
        if (!wp_next_scheduled('trm_check_updates')) {
            wp_schedule_event(time(), 'twicedaily', 'trm_check_updates');
        }
        add_action('trm_check_updates', array($this, 'scheduled_check'));
    }

    /**
     * Check for updates from GitHub
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->current_version, $remote_version, '<')) {
            $transient->response[$this->plugin_file] = (object) array(
                'slug' => 'trm-counseling-scheduler',
                'new_version' => $remote_version,
                'url' => 'https://github.com/' . $this->github_repo,
                'package' => $this->get_download_url($remote_version),
                'tested' => '6.4',
                'requires' => '5.0',
                'requires_php' => '7.2',
                'icons' => array(),
                'banners' => array(),
                'active' => true,
            );
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        $cached = get_transient($this->transient_key);
        
        if ($cached !== false) {
            return $cached;
        }

        $url = $this->github_raw_url . '/package.json';
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => apply_filters('https_local_ssl_verify', false)
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['version'])) {
            set_transient($this->transient_key, $data['version'], $this->cache_duration);
            return $data['version'];
        }

        return false;
    }

    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return 'https://github.com/' . $this->github_repo . '/releases/download/v' . $version . '/trm-counseling-scheduler.zip';
    }

    /**
     * Provide plugin information for details modal
     */
    public function plugin_info($res, $action, $args) {
        if ($action !== 'plugin_information') {
            return $res;
        }

        if (isset($args->slug) && $args->slug === 'trm-counseling-scheduler') {
            $remote_data = $this->get_remote_plugin_info();
            
            if ($remote_data) {
                return $remote_data;
            }
        }

        return $res;
    }

    /**
     * Get remote plugin information from GitHub
     */
    private function get_remote_plugin_info() {
        $url = $this->github_raw_url . '/plugin-info.json';
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => apply_filters('https_local_ssl_verify', false)
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response));
        
        if ($data) {
            return $data;
        }

        return false;
    }

    /**
     * Scheduled update check
     */
    public function scheduled_check() {
        delete_transient($this->transient_key);
        $this->get_remote_version();
    }

    /**
     * Clear update cache
     */
    public static function clear_cache() {
        delete_transient('trm_update_check');
    }
}

// Initialize updater
new TRM_Updater();
