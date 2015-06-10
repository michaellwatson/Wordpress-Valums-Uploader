<?php
/**
* Plugin Name: Upload Plugin
* Plugin URI: http://michaellwatson.co.uk
* Description: Simple Upload Image
* Version: 1.0 
* Author: Michael L Watson
* Author URI: http://michaellwatson.co.uk
* License: GPL12
*/
 /**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
           // die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
	 function clean($string) {
	   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        //echo 'hit';
        //exit();
		if (!is_writable($uploadDirectory)){
            //return false;
            array('error' => "Server error.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
		$filename = $this->clean($filename);
		$filename = str_replace(' ', '_', $filename);
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
            return array('success'=>true,  'filename'=>$filename . '.' . $ext);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        // list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$allowedExtensions = array();
		// max file size in bytes
		$sizeLimit = 10 * 1024 * 1024;

    }  
}
function uploadScreen(){
	$url = plugins_url();
	wp_enqueue_script('fileuploader', $url . '/valumsplugin/js/fileuploader/fileuploader.js', array(), '1.0.0', true );
	wp_enqueue_script('upload', $url.'/valumsplugin/js/fileuploader/uploader.js', array(), '1.0.0', true );
	wp_enqueue_style('upload', $url.'/valumsplugin/css/fileuploader/fileuploader.css');
    $html = "<div class=\"progress active imagesProgress\">";
    $html .="<div class=\"progress-bar ipb progress-bar-success\" role=\"progressbar\" aria-valuenow=\"0\" aria-valuemin=\"0\" aria-valuemax=\"100\">";
    $html .="</div>";
    $html .="</div>";
    $html .="<div id=\"file-uploader\">";       
    $html .="<noscript>";
    $html .="<p>Please enable JavaScript to use file uploader.</p>";
    $html .="<!-- or put a simple form for upload here -->";
    $html .="</noscript>";
    $html .="</div>";
    return $html;
}
add_shortcode("upload_button", "uploadScreen");  

function uploadFiles(){

	$allowedExtensions = array('jpg','jpeg','png','gif');
	// max file size in bytes
	$sizeLimit = 500 * 1024 * 1024;
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
    //print_r($uploader);
    $dir = wp_upload_dir();
	$result = $uploader->handleUpload($dir['path'].'/');
    $result['url'] = $dir['url'].'/';
    // $filename should be the path to a file in the upload directory.
    $filename = $dir['path'].'/'.$result['filename'];
    // Check the type of file. We'll use this as the 'post_mime_type'.
    $filetype = wp_check_filetype( basename( $filename ), null );
    // Get the path to the upload directory.
    $wp_upload_dir = wp_upload_dir();
    // Prepare an array of post data for the attachment.
    $attachment = array(
        'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
        'post_mime_type' => $filetype['type'],
        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    // Insert the attachment.
    $attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
    // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    // Generate the metadata for the attachment, and update the database record.
    $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    $result['attach_id'] = $attach_id;
    echo json_encode($result);

    exit();

}
add_action( 'wp_ajax_upload_image', 'uploadFiles' );
add_action( 'wp_ajax_nopriv_upload_image', 'uploadFiles' );
?>