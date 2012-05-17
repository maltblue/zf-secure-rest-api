<?php

class Common_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    const CACHE_ID_PREFIX = 'CommonAuth_';
    const HEADER_APIKEY = 'CommonApikey';
    const HEADER_REQUEST_HASH = 'CommonRequestHash';
    const AUTH_NAMESPACE = 'CommonAuth';

      protected $_authObj = NULL;
      protected $_responseObj = NULL;
      
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // get the api key from the header
        $apiKey = $this->getRequest()->getHeader(self::HEADER_APIKEY);
        
        // get the hash of the request
        $requestHash = $this->getRequest()->getHeader(self::HEADER_REQUEST_HASH);
        
        // both the api key and request hash are required
        if (!empty($apiKey) && !empty($requestHash)) {
            
            $authStorage = new Zend_Session_Namespace(self::AUTH_NAMESPACE);
            $cacheKey = self::CACHE_ID_PREFIX . $apiKey;
            $authAdapter = new Common_Auth_Adapter_Rest('tblUsers', 'apikey', 'secretkey');
            $authAdapter->setApiKey($apiKey)
                        ->setRequestHash($requestHash)
                        ->setRequestOptions($request->getParams());
            try {
                $result = $authAdapter->authenticate();
            } catch (Zend_Auth_Exception $e) {
                $this->_redirectNoAuth($request);
            }        
          } else {
            $this->_redirectNoAuth($request);
          }
    }
    
    protected function _redirectNoAuth(Zend_Controller_Request_Abstract $request)
    {
      if (($request->getParam('controller') == 'error') &&
        ($request->getParam('module') == 'default') &&
        ($request->getParam('action') == 'noauth')) {
          return;
      }
      $redir = Zend_Controller_Action_HelperBroker::getStaticHelper(
        'Redirector'
      );
      $redir->setGotoRoute(array(), 'noauth', true);
      $redir->redirectAndExit();
      }
    }