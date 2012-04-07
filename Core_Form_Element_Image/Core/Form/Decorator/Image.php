<?php

class Core_Form_Decorator_Image extends Zend_Form_Decorator_File implements Zend_Form_Decorator_Marker_File_Interface {
    
    public function render($content)
    {   
        $element = $this->getElement();
        $content = parent::render($content);
        if($element->getDontShowImage() === false) {
        
            $alt = $element->getRenderingValue();
            $imgName = $element->getPath() . $alt;
            if($element->getRenderingValue() == null) {
                $imgName = 'noPicture.png';
                $alt = $imgName;
                if(!file_exists($element->getPath() . $imgName)) {
                    $imgName = $element->getPath() . $imgName;
                } else {
                    $imgName = '/images/page/' . $imgName;
                }
            }
            $img = '<img src="' . $imgName . 
                        '" alt="' . substr($alt, 0, strrpos($alt, '.') - 1) . '" />';
            
            return '<div class="renderedImg">' . $img . '</div>' . $content;
        }
        return $content;
    }
}