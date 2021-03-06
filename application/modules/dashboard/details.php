<?php 
namespace Modules\Dashboard;
use \Module as Module;

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Content Module
 * 
 * @link        http://pagestudiocms.com
 * @author      PageStudioCMS Dev Team
 * @package     Modules
 */
class Details extends Module
{
    public static function info()
    {
        return [
            'name'          => 'Dashboard',
            'slug'          => 'dashboard',
            'description'   => 'The dashboard module is used to show Google Analytics on the dashboard of of your admin panel.',
            'version'       => '1.0',
            'addon_uri'     => 'http://pagestudiocms.com',
            'license'       => 'GPL2',
            'license_uri'   => '',
            'author'        => 'Cosmo Mathieu',
            'author_uri'    => 'http://pagestudiocms.com',
            'plugable'      => 0,
            'is_core'       => 1,
        ];
    }
    
    /**
     * Returns an array containing the module level menu items to be 
     * hooked into the admin menu
     * 
     * @return array
     */
    public static function admin_menu()
    {
        return [[
            'label' => 'Dashboard',
            'url'   => '/',
            "menu_order" => 1,
            'id'    => 'dashboard',
            'sub'   => array(),
        ]];
    }
    
    /**
     * Static method that either returns or instantiates the object
     * 
     * @return object
     */
    public static function run()
    {
        if( ! isset(self::$_instance)) {
            self::$_instance = new Details();
        }
        
        return self::$_instance;
    }
    
    public function enable()
    {
        
    }
    
    public function disable()
    {
        
    }
    
    public function install()
	{
        extract($this->info());
		// Add data to table 
        $data = [
            'module_slug' => $slug,
            'module_name' => $name,
            'module_description' => $description,
            'module_version' => $version,
            'module_options' => '',
            'has_backend' => 1,
            'has_plugin' => $plugable,
            'has_widget' => 0,
            'is_core' => $is_core,
            'is_enabled' => 1,
            'is_required' => 1,
            // 'menu_order' => 0,
		];
        
        if ( ! $this->db->insert('modules', $data)) {
            return false;
        }
		return true;
	}
    
    public function uninstall()
	{
		// This is a core module, lets keep it around.
		return false;
        // $info = $this->info();
        
        // if ( ! $this->db->delete('modules', ['module_slug' => $info['slug']])) {
            // return false;
        // }
		// return true;
	}
    
	public function upgrade($old_version)
	{
		return true;
	}
}