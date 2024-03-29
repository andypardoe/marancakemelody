<?php
/* $Id$ */
/**
 * PhpThumbComponent - A CakePHP Component to use with PhpThumb
 * Copyright (C) 2007-2008 Alex McFadyen aka Drayen
 *
 * @license MIT
 */

/**
 * PhpThumbComponent - A CakePHP Component to use with PhpThumb 
 * (http://phpthumb.sourceforge.net/)
 * 
 * Based on CakePHP tutorial on : http://bakery.cakephp.org/articles/view/274
 * 
 * This component will allow you to create/display thumbnails as well as have them
 * auto generated and cached.
 * 
 * Its set up to allow off site linking.
 * 
 * @package PhpThumbComponent
 * @subpackage controllers.components
 * 
 * @author Alex McFadyen aka Drayen
 * 
 * @version 0.1
 **/

class PhpThumbComponent extends Component{

	/**
	 * Array of errors
	 */
	var $errors = array();
	
	/**
	 * preset $_GET string varables, also defines what can be passed in the get string
	 */

	var $presets = array(
								'w'=>null,     	// Width
								'h'=>null,			// Height
								'wp'=>null,			// Width  (Portrait Images Only)
								'hp'=>null,			// Height (Portrait Images Only)
								'wl'=>null,			// Width  (Landscape Images Only)
								'hl'=>null,			// Height (Landscape Images Only)
								'ws'=>null,			// Width  (Square Images Only)
								'hs'=>null,			// Height (Square Images Only)
								'q'=>75,				// jpeg output Quality
								'zc'=>0,				// Zoom Crop
								'sx'=>null,			// Source crop top-left X position
								'sy'=>null,			// Source crop top-left Y position
								'sw'=>null,			// Source crop Width
								'sh'=>null,			// Source crop Height
								'far'=>1,			// Fixed Aspect Ratio
								'iar'=>null,		// Ignore Aspect Ratio
								'ra'=>null,			// Rotate by Angle
								'ar'=>null,			// Auto Rotate X=based on exif data, L=always landscape, P=portrait
								'aoe'=>1,			// Allow Uutput Enlargment
								'bc'=>null,			// Border Color
								'bg'=>'FFFFFF',	// Background Colour
								'fltr[]'=>null,	// FiLTeRs (crazy stuff)
								'err'=>null			// default ERRor image filename
								);
	
	/**
	 * The mime types that are allowed for images
	 */
	var $allowed_mime_types = array(IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_PNG);

	/**
	 * File system location to save thumbnails to.  
	 */
	var $cache_location = null;

	/**
	 * How old the cached image can be before its updated.
	 */
	var $max_cache_age = 1; #7776000; #90 days in seconds
	
	/**
	 * Size in bytes that the cashe folder can reach before it over writes old images,
	 * comment out to disable
	 */
	var $max_cache_size = 10485760; //10 * 1024 * 1024;
	

	var $controller;
	var $model;

	function startup( &$controller ) {
		$this->controller = &$controller;
		if(isset($controller->max_cache_age)){
			$this->max_cache_age = $controller->max_cache_age;
		}		
		$this->cache_location = CACHE.'thumbs'.DS;
	}


	
	/**
	 * Will generate a thumbnail as defined by the presets (or by $_GET vars)
	 * and place it in the target. If display = true it will also output the 
	 * thumbnail.
	 * 
	 * @param string $source the location of the source image (may be relative or absolute)
	 * @param string $target the target directory and filename for the generated thumbnail
	 * @param bool $overwrite if the target should be overwritten
	 * @param bool $display if the image should be displayed
	 * @return bool Success?
	 * @author Alex McFadyen
	 */
	function generateThumbnail($source = null, $target = null, $overwrite = true, $display = false){

		$target_dir = substr($target, 0, -(strpos(strrev($target),'/')));

		if($source == null OR $target == null){//check correct params are set
			$this->addError("Both source[$source] and target[$target] must be set");
			return false;/*
		}elseif(!is_file($source)){//check source is a file
			$this->addError("Source[$source] is not a valid file");
			return false;*/
		}elseif(in_array($this->ImageTypeToMIMEtype($source), $this->allowed_mime_types)){//and is of allowed type
			$this->addError("Source[$source] is not a valid file type");
			return false;
		}elseif(!is_writable($target_dir)){//check if target directory is writeable
			$this->addError("Can not write to target directory [$target_dir]");
			return false;
		}elseif(is_file($target) AND !$overwrite){//check if target is a file already and not ok to be over written
			$this->addError("Target[$target] exsists and overwrite is not true");
			return false;
		}elseif(is_file($target) AND !is_writable($target)){
			$this->addError("Can not overwrite Target[$target]");
			return false;
		}

		//load PhpThumb
		vendor('phpThumb'.DS.'phpthumb.class');
		$phpThumb = new phpThumb();
		
		//set presets
		$phpThumb->config_nohotlink_enabled = false;
		$phpThumb->config_nooffsitelink_enabled = false;
		$phpThumb->config_prefer_imagemagick = true;
		$phpThumb->config_output_format = 'jpeg';
		$phpThumb->config_error_die_on_error = true;
		$phpThumb->config_allow_src_above_docroot = true;
		
		
		//optionals 
		if(isset($this->max_cache_size)) $phpThumb->config_cache_maxsize = $this->max_cache_size;
		
		//load in source image
		$phpThumb->setSourceFilename($source);
		
		//load vars from $_GET if they are set
		foreach($this->presets as $key=>$value) {
			if(isset($_GET[$key])) {
				$phpThumb->setParameter($key, $_GET[$key]);
			} else {
				if($value !== null) {
					$phpThumb->setParameter($key, $value);
				}
			}
		}
		
			
		//create the thumbnail
		if($phpThumb->generateThumbnail()){
			if(!$phpThumb->RenderToFile($target)){
				$this->addError('Could not render file to: '.$target);
			}elseif($display==true){
				$phpThumb->OutputThumbnail();	
				die();//not perfect, i know but it insures cake doenst add extra code after the image.
			}
		} else {
			$this->addError('could not generate thumbnail');
		}

		// if we have any errors, remove any thumbnail that was generated and return false
		if(count($this->errors)>0){
			if(file_exists($target)){
				unlink($target);
			}
			return false;
		} else return true;
	}
	
	
	
	/**
	 * Display and/or generate a auto-named thumbnail, based on presets in $_GET.
	 *
	 * @param string $source the location of the source image (may be relative or absolute)
	 * @param bool $forceUpdate if the thumbnal should be refreashed
	 * @param bool $display if the image should be displayed
	 * @return bool Success?
	 * @author Alex McFadyen
	 */
	function displayThumbnail($source, $forceUpdate = false, $display = true){
		if($source == null) $source = $_GET['src'];

		$cache_filename = $this->cache_location . md5(env('REQUEST_URI')) . '_' . md5($source).'.jpg';
	
		#check the cache'ed image exsists and its new enough and that it needs to be displayed
		if(is_file($cache_filename) //file exsists
				AND (time() < filectime($cache_filename) + $this->max_cache_age) //not too old
				AND (is_file($source) ? ( filectime($cache_filename) > filectime($source) ) : true) //cached image is newer than source
				AND ($display == true) 
				AND !($forceUpdate == true)) 
			{

			header('Content-Type: '.IMAGETYPE_JPEG);
			@readfile($cache_filename);

			exit();//not perfect, i know but it insures cake doenst add extra code after the image.
		}else{
			return $this->generateThumbnail($source, $cache_filename, true, $display);
		}
	}
	
	/**
	 * Function borrowed form phpThumb libs
	 */
	function ImageTypeToMIMEtype($imagetype) {
		if (function_exists('image_type_to_mime_type') && ($imagetype >= 1) && ($imagetype <= 16)) {
			// PHP v4.3.0+
			return image_type_to_mime_type($imagetype);
		}
		static $image_type_to_mime_type = array(
			1  => 'image/gif',                     // IMAGETYPE_GIF
			2  => 'image/jpeg',                    // IMAGETYPE_JPEG
			3  => 'image/png',                     // IMAGETYPE_PNG
			4  => 'application/x-shockwave-flash', // IMAGETYPE_SWF
			5  => 'image/psd',                     // IMAGETYPE_PSD
			6  => 'image/bmp',                     // IMAGETYPE_BMP
			7  => 'image/tiff',                    // IMAGETYPE_TIFF_II (intel byte order)
			8  => 'image/tiff',                    // IMAGETYPE_TIFF_MM (motorola byte order)
			9  => 'application/octet-stream',      // IMAGETYPE_JPC
			10 => 'image/jp2',                     // IMAGETYPE_JP2
			11 => 'application/octet-stream',      // IMAGETYPE_JPX
			12 => 'application/octet-stream',      // IMAGETYPE_JB2
			13 => 'application/x-shockwave-flash', // IMAGETYPE_SWC
			14 => 'image/iff',                     // IMAGETYPE_IFF
			15 => 'image/vnd.wap.wbmp',            // IMAGETYPE_WBMP
			16 => 'image/xbm',                     // IMAGETYPE_XBM
	
			'gif'  => 'image/gif',                 // IMAGETYPE_GIF
			'jpg'  => 'image/jpeg',                // IMAGETYPE_JPEG
			'jpeg' => 'image/jpeg',                // IMAGETYPE_JPEG
			'png'  => 'image/png',                 // IMAGETYPE_PNG
			'bmp'  => 'image/bmp',                 // IMAGETYPE_BMP
			'ico'  => 'image/x-icon',
		);

		return (isset($image_type_to_mime_type[$imagetype]) ? $image_type_to_mime_type[$imagetype] : false);
	}
	
	/**
	 * Clears the current set cache directory of expired files (or all) images
	 *
	 * @param bool $clearAll if set, it will clear all the files in the cache directory
	 * @return bool true
	 * @author Alex McFadyen
	 */
	function clearCache($clearAll = false){
		$files = glob($this->cache_location.'*.jpg');
		foreach($files as $file)
			if($clearAll OR time() < filectime($file))
				unlink($file);
			
		return true;
	}

	function addError($msg){
		$this->errors[] = $msg;
	}
}
?>
