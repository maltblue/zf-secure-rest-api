<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initPlugins()
    {
      $this->bootstrap('Db');
      $this->_db = $this->getResource('Db');
      $front = Zend_Controller_Front::getInstance();
      $front->registerPlugin(
          new Common_Controller_Plugin_Auth(
            array('db' => $this->_db)
          )
      );
    }

}

