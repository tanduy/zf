<?php

require_once 'Zend/Build/Resource/Directory.php';

class Zend_Build_Resource_ImagesDirectory extends Zend_Build_Resource_Directory
{
    
    public function init()
    {
        $this->_parameters['name'] = 'images';
    }
    
}