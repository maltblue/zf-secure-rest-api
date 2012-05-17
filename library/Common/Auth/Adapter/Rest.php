<?php

class Common_Auth_Adapter_Rest implements Zend_Auth_Adapter_Interface
{

    /**
     * Database Connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_zendDb = null;

    /**
     * @var Zend_Db_Select
     */
    protected $_dbSelect = null;

    /**
     * $_tableName - the table name to check
     *
     * @var string
     */
    protected $_tableName = null;

    /**
     * $_identityColumn - the column to use as the identity
     *
     * @var string
     */
    protected $_identityColumn = null;

    /**
     * $_credentialColumns - columns to be used as the credentials
     *
     * @var string
     */
    protected $_secretKeyColumn = null;

    /**
     * $_identity - Identity value
     *
     * @var string
     */
    protected $_apiKey = null;


    /**
     * $_credential - Credential values
     *
     * @var string
     */
    protected $_requestHash = null;

    /**
     * $_credential - Credential values
     *
     * @var string
     */
    protected $_requestOptions = null;

    /**
     * $_authenticateResultInfo
     *
     * @var array
     */
    protected $_authenticateResultInfo = null;

    /**
     * $_resultRow - Results of database authentication query
     *
     * @var array
     */
    protected $_resultRow = null;
    
    protected $_ambiguityIdentity = false;
    
    public function __construct($tableName = null, $identityColumn = null, $secretKeyColumn = null)
    {
        $this->_setDbAdapter();
        
        if (!is_null($tableName)) {
            $this->setTableName($tableName);
        }
        
        if (!is_null($identityColumn)) {
            $this->setIdentityColumn($identityColumn);
        }
             
        if (!is_null($secretKeyColumn)) {
            $this->setSecretyKeyColumn($secretKeyColumn);
        }
    }

    protected function _setDbAdapter(Zend_Db_Adapter_Abstract $zendDb = null)
    {
        $this->_zendDb = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $this;
    }

    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;
        return $this;
    }

    public function setIdentityColumn($identityColumn)
    {
        $this->_identityColumn = $identityColumn;
        return $this;
    }

    public function setSecretyKeyColumn($secretKeyColumn)
    {
        $this->_secretKeyColumn = $secretKeyColumn;
        return $this;
    }

    public function setRequestOptions($requestOptions)
    {
        $this->_requestOptions = $requestOptions;
        unset($this->_requestOptions['module']);
        unset($this->_requestOptions['controller']);
        unset($this->_requestOptions['action']);
        return $this;
    }

    public function setRequestHash($requestHash)
    {
        $this->_requestHash = $requestHash;
        return $this;
    }

    public function setApiKey($value)
    {
        $this->_apiKey = $value;
        return $this;
    }

    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }

    public function getDbSelect()
    {
        if ($this->_dbSelect == null) {
            $this->_dbSelect = $this->_zendDb->select();
        }

        return $this->_dbSelect;
    }

    public function getResultRowObject($returnColumns = null, $omitColumns = null)
    {
        if (!$this->_resultRow) {
            return false;
        }

        $returnObject = new stdClass();

        if (null !== $returnColumns) {

            $availableColumns = array_keys($this->_resultRow);
            foreach ( (array) $returnColumns as $returnColumn) {
                if (in_array($returnColumn, $availableColumns)) {
                    $returnObject->{$returnColumn} = $this->_resultRow[$returnColumn];
                }
            }
            return $returnObject;

        } elseif (null !== $omitColumns) {

            $omitColumns = (array) $omitColumns;
            foreach ($this->_resultRow as $resultColumn => $resultValue) {
                if (!in_array($resultColumn, $omitColumns)) {
                    $returnObject->{$resultColumn} = $resultValue;
                }
            }
            return $returnObject;

        } else {

            foreach ($this->_resultRow as $resultColumn => $resultValue) {
                $returnObject->{$resultColumn} = $resultValue;
            }
            return $returnObject;

        }
    }
    

    public function setAmbiguityIdentity($flag)
    {
        if (is_integer($flag)) {
            $this->_ambiguityIdentity = (1 === $flag ? true : false);
        } elseif (is_bool($flag)) {
            $this->_ambiguityIdentity = $flag;
        }
        return $this;
    }

    public function getAmbiguityIdentity()
    {
        return $this->_ambiguityIdentity;
    }
    
    protected function _authenticateSetup()
    {
        $this->_authenticateResultInfo = array(
            'code'     => Zend_Auth_Result::FAILURE,
            'identity' => '',
            'messages' => array()
            );

        return true;
    }
    
    protected function _authenticateCreateSelect()
    {
        // get select
        $dbSelect = clone $this->getDbSelect();
        $dbSelect->from($this->_tableName, array('*'))
                 ->where($this->_zendDb->quoteIdentifier($this->_identityColumn, true) . ' = ?', $this->_apiKey);

        return $dbSelect;
    }
    
    protected function _authenticateHash($secretKey)
    {   
        $requestHash = hash_hmac('sha1', implode(",", $this->_requestOptions), $secretKey, FALSE);
        if ($requestHash === $this->_requestHash) {
            return true;
        }
        return false;
    }
    
    protected function _authenticateQuerySelect(Zend_Db_Select $dbSelect)
    {
        try {
            if ($this->_zendDb->getFetchMode() != Zend_DB::FETCH_ASSOC) {
                $origDbFetchMode = $this->_zendDb->getFetchMode();
                $this->_zendDb->setFetchMode(Zend_DB::FETCH_ASSOC);
            }            
            $resultIdentities = $this->_zendDb->fetchAll($dbSelect->__toString());
            if (isset($origDbFetchMode)) {
                $this->_zendDb->setFetchMode($origDbFetchMode);
                unset($origDbFetchMode);
            }
        } catch (Exception $e) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('The supplied parameters to Zend_Auth_Adapter_DbTable failed to '
                                                . 'produce a valid sql statement, please check table and column names '
                                                . 'for validity.', 0, $e);
        }
        return $resultIdentities;
    }
    
    protected function _authenticateCreateAuthResult()
    {
        return new Zend_Auth_Result(
            $this->_authenticateResultInfo['code'],
            $this->_authenticateResultInfo['identity'],
            $this->_authenticateResultInfo['messages']
            );
    }
    
    protected function _authenticateValidateResultSet(array $resultIdentities)
    {

        if (count($resultIdentities) < 1) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $this->_authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
            return $this->_authenticateCreateAuthResult();
        } elseif (count($resultIdentities) > 1 && false === $this->getAmbiguityIdentity()) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $this->_authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
            return $this->_authenticateCreateAuthResult();
        }

        return true;
    }
    
    protected function _authenticateValidateResult($resultIdentity)
    {
        $zendAuthCredentialMatchColumn = $this->_zendDb->foldCase('zend_auth_credential_match');

        if ($this->_authenticateHash($resultIdentity['secretkey'])) {
            $this->_resultRow = $resultIdentity;
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::SUCCESS;
            $this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
        }
        
        return $this->_authenticateCreateAuthResult();
    }
    
    public function authenticate()
    {
        $this->_authenticateSetup();
        $dbSelect = $this->_authenticateCreateSelect();
        $resultIdentities = $this->_authenticateQuerySelect($dbSelect);

        if ( ($authResult = $this->_authenticateValidateResultSet($resultIdentities)) instanceof Zend_Auth_Result) {
            return $authResult;
        }

        if (true === $this->getAmbiguityIdentity()) {
            $validIdentities = array ();
            $zendAuthCredentialMatchColumn = $this->_zendDb->foldCase('zend_auth_credential_match');
            foreach ($resultIdentities as $identity) {
                if (1 === (int) $identity[$zendAuthCredentialMatchColumn]) {
                    $validIdentities[] = $identity;
                }
            }
            $resultIdentities = $validIdentities;
        }

        $authResult = $this->_authenticateValidateResult(array_shift($resultIdentities));
        return $authResult;
    }
}