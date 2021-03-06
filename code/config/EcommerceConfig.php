<?php



/**
 * This Class creates an array of configurations for e-commerce.
 * This class replaces static variables in individual classes, such as Blog::$allow_wysiwyg_editing
 *
 * @see http://en.wikipedia.org/wiki/YAML#Examples
 * @see thirdparty/spyc/spyc.php
 *
 * # HOW TO USE IT
 *
 * 1. Copy ecommerce/_config/ecommerce.yml and move it your project folder, e.g. mysite/_config/ecommerce.yml
 * In the copied file, set your configs as you see fit, using the YAML format.  E.g.
 *
 * Order:
 * 	Test: 1
 *
 * Next, include in ecommerce.yml file:
 * <code yml>
 * EcommerceConfig:
 *   folder_and_file_locations:
 *     - "mysite/_config/ecommerce.yml"
 *     - "myotherconfig.yaml"
 * </code>
 *
 * Then, in individual classes, you can access configs like this:
 *
 * <code>
 * EcommerceConfig::get("OrderAddress", "include_bla_bla_widget");
 * </code>
 *
 * OR
 *
 * <code>
 * EcommerceConfig::get($this->ClassName, "include_bla_bla_widget");
 * </code>
 *
 * if you are using PHP 5.3.0+ then you can write this in a static method
 *
 * <code>
 * EcommerceConfig::get("MyClassName", "include_bla_bla_widget");
 * </code>

 * Even though there is no direct connection, we keep linking statics to invidual classes.
 * We do this to (a) group configs (b) make it more interchangeable with other config systems.
 * One of the problems now is to know what "configs" are used by individual classes.
 * Therefore, it is important to clearly document that at the top of each class.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceConfig extends Object {

	/**
	 * Singleton
	 *
	 * @var EcommerceConfig
	 */
	private static $singleton = null;

	/**
	 * Returns a configuration.  This is the main static method for this Object.
	 * @see Config::get()
	 */
	static function get($className, $identifier, $sourceOptions = 0, $result = null, $suppress = null ) {
		return Config::inst()->get($className, $identifier, $sourceOptions, $result, $suppress);
	}

	/**
	 * The location(s) of the .yaml fixture file, relative to the site base dir
	 *
	 * @var Array
	 */
	private static $folder_and_file_locations = array("ecommerce/_config/ecommerce.yml");

	/**
	 * Array of fixture items
	 *
	 * @var array
	 */
	private $fixtureDictionary = array();

	/**
	 * loads data from file.
	 * We have this method to create a complete list of configs
	 */
	private function loadData(){
		require_once 'thirdparty/spyc/spyc.php';
		$filesArray = $this->fileLocations();
		foreach($filesArray as $folderAndFileLocation){
			$fixtureFolderAndFile = Director::baseFolder().'/'. $folderAndFileLocation;
			if(!file_exists($fixtureFolderAndFile)) {
				user_error('No custom configuration has been setup for Ecommerce - I was looking for: "' . $fixtureFolderAndFile . '"', E_USER_NOTICE);
			}
			$parser = new Spyc();
			$newArray = $parser->loadFile($fixtureFolderAndFile);
			$this->fixtureDictionary = array_merge($newArray, $this->fixtureDictionary);
		}
	}

	/**
	 * returns the complete Array of data
	 * @return Array
	 */
	public function getCompleteDataSet($refresh = false){
		if($refresh) {
			$this->loadData();
		}
		elseif(!count($this->fixtureDictionary)) {
			$this->loadData();
		}
		return $this->fixtureDictionary;
	}

	/**
	 * returns a list of file locations
	 * @return Array
	 */
	public function fileLocations() {
		$array = $this->config()->get('folder_and_file_locations');
		//we reverse it so the default comes last
		return array_reverse($array);
	}


}
