<?php

namespace OT_Update;

class Updater {
    public $current_version;
    public $update_url;
    public $plugin_slug;
    public $plugin_name;
    public $api_base;


    /**
     * @param mixed $api_base api base url for getting plugin info and version
     * @param mixed $slug plugin slug (folder + filename.php)
     * @param string $version version on of the current installed plugin
     * @param string $homepage_url plugin marketing page url
     * @param string $license optional key to authenticate premium plugin updates
     */
    public function __construct($api_base, $slug, $version, $homepage_url='', $license=''){
        $this->api_base = $api_base;
        $this->current_version = $version; 
        $this->plugin_name = str_replace('.php','', explode('/', $slug)[1]); // plugin
        $this->plugin_slug = $slug; // plugin/plugin.php
        $this->homepage_url = $homepage_url;
        $this->license = $license;
    }

    public function set_hooks(){
        add_filter( 'pre_set_site_transient_update_plugins', [self::class, 'update_check'] );
        add_filter( 'plugins_api', [self::class, 'get_info'], 10,3 );
    }
    
    public function update_check($transient){
        if (empty($transient->checked)) {
            return $transient;
        }
        // Get the remote version 
        $remote_version = $this->remote_version();
        // If a newer version is available, add the update 
        if (version_compare($this->current_version, $remote_version, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $remote_version;
            $obj->url = $this->homepage_url; 
            $obj->package = $this->api_base . '/update' . http_build_query([ 'license'=>$this->license ]) ;
            $transient->response[$this->plugin_name] = $obj;
        }
        return $transient;
    }

    public function get_info($obj, $action, $arg){
        if(
            ($action === 'query_plugins' || $action === 'plugin_information')
            && isset($arg->slug) && $args->slug = $this->plugin_slug;
        ){
            return $this->get_remote('get-info');
        }
        return $obj;
    }

    private function remote_version(){
        return $this->get_remote('version');
    }

    private function get_remote($action='get-info'){
        $response = wp_remote_get($this->api_base . '/' . $action);
        if(!is_wp_error($response) || wp_remote_retrieve_response_code($response) == 200){
            //@ operator to suppress errors from decoding json
            return @json_decode($response['body']);
        }
    }

}
