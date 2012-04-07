<?php
class Core_Form_Element_Image extends Zend_Form_Element_File
{
    public $options;
    
    //image
    protected $_crop = false;
    protected $_width = 500;
    protected $_height = 500;
    protected $_destDir = '/images/'; // a path where you want the uploaded file to be stored
    //thumb
    protected $_thumb = true; //thumbs On, Off
    protected $_cropThumb = true;
    protected $_thumbHeight = 100;
    protected $_thumbWidth = 100;
    protected $_thumbDir = "thumbs/";
    //thumb 2
    protected $_thumb2 = false; //thumbs On, Off
    protected $_cropThumb2 = true;
    protected $_thumbHeight2 = 200;
    protected $_thumbWidth2 = 200;
    protected $_thumbDir2 = "thumbs2/";
    protected $_newName = null;
	
    /**
     * As we cannot set value to input -> type="file"
     * We need additional parameter to store value from database for further rendering
     * @var <type>
     */
    protected $_renderingValue = null;
    protected $_dontShowImage = false;
	protected $_override = false;
    protected $_wiImageError = false;
    
    //cropping/resizing
    /**
     * fit:	
     * inside: the image will fit inside the new dimensions while maintaining the aspect ratio.
     * fill: the image will completely fit inside the new dimensions.
     * outside: the image will fit outside of the new dimensions, which means that it will be resized to completely fill the specified rectangle,
     * while still maintaining aspect ratio. 			
     */
    //must stay as it is if crop = true;
    protected $_fit = "outside";
    protected $_thumbFit = "outside";
    protected $_thumbFit2 = "outside";
    protected $_allowedExtensions = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');
  
    public function __construct($spec, $destDir, $options = null) {         
        $this->setDestDir($destDir, false);
        parent::__construct($spec, $options);
                       
        $this->_destDir = realpath(PUBLIC_PATH . $this->_destDir);
    }
    
    /**
     * Does all the magic and sets property newName with new file name  
     * 
     * @param name - field name
     * 
     * @return void
     */
    public function resize($name) {
        if(isset($_FILES[$name]) && !empty($_FILES[$name])) {
            $img = $this->resizeImage($_FILES[$name]);
            $this->_newName = $img;
        }
    }        
    
    public function getNewName() {
        return $this->_newName;
    }
           
    public function getPath() {
        $path = $this->_destDir;
        if(strpos($this->_destDir, PUBLIC_PATH) !== false) {
            $path = substr($this->_destDir, strlen(PUBLIC_PATH));
        }
        if($this->_thumb) $path .= $this->_thumbDir;
        
        return $path;
    }
    
    public function setDestDir($dir, $wholePath = true) {
        ($wholePath === true && strpos($dir, PUBLIC_PATH) === false) ? $path = PUBLIC_PATH : $path = '';    
        $this->_destDir = (string) $path . $dir;
    }
    
    public function setCrop($crop) {
        $this->_crop = (boolean) $crop;
    }
    
    public function setWidth($width) {
        $this->_width = (int)$width;
    }
    
    public function setHeight($height) {
        $this->_height = (int)$height;
    }
    
    public function setThumb($thumb) {
        $this->_thumb = (boolean)$thumb;
    }
    
    public function setThumb2($thumb) {
        $this->_thumb2 = (boolean)$thumb;
    }
    
    public function setCropThumb($crop) {
        $this->_cropThumb = (boolean) $crop;
    }
    
    public function setThumbWidth($width) {
        $this->_thumbWidth = (int)$width;
    }
    
    public function setThumbHeight($height) {
        $this->_thumbHeight = (int)$height;
    }
    
    public function setThumbDir($dir) {
        $this->_thumbDir = (string)$dir;
    }
    
    public function setCropThumb2($crop) {
        $this->_cropThumb = (boolean) $crop;
    }
    
    public function setThumbWidth2($width) {
        $this->_thumbWidth = (int)$width;
    }
    
    public function setThumbHeight2($height) {
        $this->_thumbHeight = (int)$height;
    }
    
    public function setThumbDir2($dir) {
        $this->_thumbDir = (string)$dir;
    }
    
    public function setOverride($override) {
        $this->_override = (boolean)$override;
    }
    
    public function setDontShowImage($boolean) {
        $this->_dontShowImage = (boolean) $boolean;
    }
    
    public function getDontShowImage() {
        return $this->_dontShowImage;
    }
    

    /**
     *
     * @param <type> $renderingValue
     * @return Core_Form_Element_Image
     */
    public function setRenderingValue($renderingValue)
    {
        $this->_renderingValue = $renderingValue;
        return $this;
    }

    /**
     *
     * @return <type>
     */
    public function getRenderingValue()
    {
        return $this->_renderingValue;
    }

    public function deleteImage($val) {
	$this->_destDir = rtrim($this->_destDir, '/\\') . DIRECTORY_SEPARATOR;

        unlink($this->_destDir . $val);
        if($this->_thumb)
            unlink($this->_destDir . $this->_thumbDir . $val);
        if($this->_thumb2)
            unlink($this->_destDir . $this->_thumbDir2 . $val);
    }
    
    public function isValid($value, $context = null) {
        $valid = parent::isValid($value, $context);
        
        // do some custom validation, at least these messages get displayed on the form now.
        if($this->_wiImageError) {
            $this->addError($this->_wiImageError); 
            $valid = false;    
        }
        
        return $valid;
    }
    
    /**
     * Loads, resizes and saves image. It creates thumb, thumb2 and normal picture.
     * You have two options - thumbs can be cropped or resized keeping proportion
     * 
     * @param files - source directory
     * @param imageName
     * 
     * @return imageName
     */
    protected function resizeImage(array $files) {
        if(empty($files['name'])) {
            return false;
        }
        
        require_once(APPLICATION_PATH . '/../library/wideimage/WideImage.inc.php');

        $srcDir = isset($files['tmp_name']) ? $files['tmp_name'] : null; 
        try {
            $extension = $this->isFileTypeAllowed($files);
            $this->checkIfDirsExists();
    
            $image = $this->createName($files['name']);
    
            $image = $image . "." . $extension;
        
            $img = wiImage::load($srcDir, $extension);  
        } catch(Exception $e) {
            $this->_wiImageError = $e->getMessage();
            return false;
        }
        
        //thumb
        if ($this->_thumb) {
            //first resize image to fit as much as possible before cropping
            $resized = $img->resize($this->_thumbWidth, $this->_thumbHeight, $this->_thumbFit);
            if ($this->_cropThumb) {
                //calculate where to crop
                $whereToCropThumb = $this->whereToCrop($srcDir, $this->_thumbWidth, $this->_thumbHeight);
                //crop
                $resized = $resized->crop($whereToCropThumb[0], $whereToCropThumb[1], $this->_thumbWidth, $this->_thumbHeight);
            }

            $resized->saveToFile($this->_destDir . $this->_thumbDir . $image);
        }
        //thumb2 - same as for thumb
        if ($this->_thumb2) {
            $resized = $img->resize($this->_thumbWidth2, $this->_thumbHeight2, $this->_thumbFit2);
            if ($this->_cropThumb2) {
                $whereToCropThumb2 = $this->whereToCrop($srcDir, $this->_thumbWidth2, $this->_thumbHeight2);
                $resized = $resized->crop($whereToCropThumb2[0], $whereToCropThumb2[1], $this->_thumbWidth2, $this->_thumbHeight2);
            }

            $resized->saveToFile($this->_destDir . $this->_thumbDir2 . $image);
        }
        //image - same as for thumb and thumb2
        //if height and width is set to -1, don' resize
        if ($this->_width == -1 && $this->_height == -1) {
            $resized = $img;
        } else {
            $resized = $img->resize($this->_width, $this->_height, $this->_fit);
        }
        if ($this->_crop) {
            $whereToCrop = $this->whereToCrop($srcDir, $this->_width, $this->_height);
            $resized = $resized->crop($whereToCrop[0], $whereToCrop[1], $this->_width, $this->_height);
        }

        $resized->saveToFile($this->_destDir . $image);

        return $image;
    }
    
    public function loadDefaultDecorators() {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }
          
        $decorators = $this->getDecorators();
        if(empty($decorators)) {
          $this->addDecorator('Image')
               ->addDecorator('Errors')
               ->addDecorator('Label')
               ->addDecorator('HtmlTag', array('tag' => 'li'));
        }
    }  
    
        
    protected function checkIfDirsExists() {
        if (!is_dir($this->_destDir)) {
            throw new Core_Exception("Error! Folder " . $this->_destDir . " for images does not exist!");
        }
        if ($this->_thumb) {
            $thumbDir = $this->_destDir . $this->_thumbDir;
            if (!is_dir($thumbDir)) {
                throw new Core_Exception("Error! Folder " . $thumbDir . " for images does not exist!");
            }
        }
        if ($this->_thumb2) {
            $thumbDir2 = $this->_destDir . $this->_thumbDir2;
            if (!is_dir($thumbDir2)) {
                throw new Core_Exception("Error! Folder " . $thumbDir2 . " for images does not exist!");
            }
        }
    }

    /**
     * Gets images width
     * 
     * @param srcDir - path to source directory
     * @return image width
     */
    protected function getImgWidth($srcDir) {
        $img = getimagesize($srcDir);

        return $img[0];
    }

    /**
     * Gets images height
     * 
     * @param srcDir - path to source directory
     * @return image height
     */
    protected function getImgHeight($srcDir) {
        $img = getimagesize($srcDir);

        return $img[1];
    }

    /**
     * Calculates where to crop picture from left
     *
     * @param srcDir
     * @param wantedWidth
     * @param wantedHeight
     *
     * @return array
     * */
    protected function whereToCrop($srcDir, $wantedWidth, $wantedHeight) {
        //get image width
        $width = $this->getImgWidth($srcDir);
        $height = $this->getImgHeight($srcDir);
        //Caluclate which is the best way to resize image
        $factorWidth = $width / $wantedWidth;
        $factorHeight = $height / $wantedHeight;

        //Calculate where to crop according to ratio
        if ($factorWidth > $factorHeight) {
            $resizedWidth = $width / $factorHeight;
            $left = ($resizedWidth - $wantedWidth) / 2;
            $left = ceil($left);
            return array($left, 0);
        } else {
            $resizedHeight = $height / $factorWidth;
            $top = ($resizedHeight - $wantedHeight) / 2;
            $top = ceil($top);
            return array(0, $top);
        }
    }

    /**
     * Generates new name for uploaded image
     * 
     * @return new filename
     */
    protected function createName($imageName) {
        if($imageName == null) {
            return false;
        }
        //replace all strange chars
        $imageName = preg_replace('/[^a-z0-9_\-\.]/i', '', $imageName);
        $name = explode(".", $imageName);
        
        $i = 1;
        if(!$this->getOverride) {
            $fileName = $name[0];
			while(file_exists($this->_destDir . $name[0] . '.' . $name[1])) {
			    $name[0] = $fileName . '-' . $i;
                $i++;
	  		}
  		}

        return $name[0];
    }

    /**
     * Checks if uploaded file is image
     * 
     * @param $file - $_FILES array
     * @return string
     */
    protected function isFileTypeAllowed($files) {
        $this->addValidators(array(array('Extension', false, 'jpg,jpeg,png,gif'), 
                                array('IsImage', false, 'jpg,jpeg,png,gif'))
                             );
                             
        if(!isset($files['name']) || !isset($files['type'])) {
            return false;
        }
        $extension = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
        $fileName = strtolower($files['name']); //capital letter (.JPG ...)
        
        if (is_array($this->_allowedExtensions)) {
            foreach ($this->_allowedExtensions as $Ext) {
                if (strtolower($files['type']) == $Ext) {
                    return $extension;
                }
            }
        }
        
        throw new Exception("Napaka! Nalagate lahko le gif, jpg, jpeg in png datoteke.");
    }
}