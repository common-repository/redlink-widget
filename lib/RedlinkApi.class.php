<?php

class RedlinkApi {

    var $apiLogin;
    var $apiPasswd;
    var $apiUrlContacts = 'http://redlink.pl/ws/v1/Soap/Contacts/Contacts.asmx?WSDL';
    var $apiUrlGroups = 'http://redlink.pl/ws/v1/Soap/Contacts/Groups.asmx?WSDL';
	var $soapClient;
	var $lastResult;
    var $errorMessage;
    var $errorCode;
    
    function RedlinkApi($username, $password) {
        $this->apiLogin  = $username;
        $this->apiPasswd = $password;
    }
	
    function searchContacts($data, $offset = 0, $limit = 100) {
		try {
			$this->soapClient   = new SoapClient($this->apiUrlContacts);
			$param              = null;
			$param->strUserName = $this->apiLogin;
			$param->strPassword = $this->apiPasswd;
			$param->data        = $data;
			$param->iOffset     = $offset;
			$param->iLimit      = $limit;
			$this->lastResult   = $this->soapClient->SearchContacts($param);

			if ($this->apiError()) {
				return false;
			} else {
				if (empty($this->lastResult->SearchContactsResult->DataArray->ContactData)) {
					return array();
				} else {
					return $this->lastResult->SearchContactsResult->DataArray->ContactData;
				}
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function filterContactPhone($phone) {
		$phone = str_replace(array(' ', '.', '-', '_', '/', '(', ')', '[', ']'), '', trim($phone));
		$phone = '0' . ltrim($phone, '0');
		return $phone;
    }

    function addContact($data) {
		try {

			if (isset($data['MobilePhone'])) {
				$data['MobilePhone'] = $this->filterContactPhone($data['MobilePhone']);
			}

			if (isset($data['StatPhone'])) {
				$data['StatPhone'] = $this->filterContactPhone($data['StatPhone']);
			}

			$this->soapClient   = new SoapClient($this->apiUrlContacts);
			$param              = null;
			$param->strUserName = $this->apiLogin;
			$param->strPassword = $this->apiPasswd;
			$param->data        = $data;
			$this->lastResult   = $this->soapClient->addContact($param);
			
			if ($this->apiError()) {
				return false;
			} else {
				if (empty($this->lastResult->AddContactResult->Data)) {
					return false;
				} else {
					return $this->lastResult->AddContactResult->Data;
				}
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function changeContact($contact, $data) {
		try {

			if (isset($data['MobilePhone'])) {
				$data['MobilePhone'] = $this->filterContactPhone($data['MobilePhone']);
			}

			if (isset($data['StatPhone'])) {
				$data['StatPhone'] = $this->filterContactPhone($data['StatPhone']);
			}

			$this->soapClient    = new SoapClient($this->apiUrlContacts);
			$param               = null;
			$param->strUserName  = $this->apiLogin;
			$param->strPassword  = $this->apiPasswd;
			$param->strContactId = $contact;
			$param->data         = $data;
			$this->lastResult    = $this->soapClient->ChangeContact($param);
			
			if ($this->apiError()) {
				return false;
			} else {
				return true;
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function validateContactPhone($phone) {
		return 'mobile';
		return 'stat';
		return false;
		try {

			$this->soapClient   = new SoapClient($this->apiUrlContacts);
			$param              = null;
			$param->strUserName = $this->apiLogin;
			$param->strPassword = $this->apiPasswd;
			$param->strPhone    = $this->filterContactPhone($phone);
			$this->lastResult   = $this->soapClient->validateContactPhone($param);
			
			if ($this->apiError()) {
				return false;
			} else {
				if (empty($this->lastResult->validateContactPhoneResult->Data)) {
					return false;
				} else {
					return $this->lastResult->validateContactPhoneResult->Data;
				}
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function addContactsToGroup($contacts, $group) {
		try {
			$this->soapClient    = new SoapClient($this->apiUrlContacts);
			$param               = null;
			$param->strUserName  = $this->apiLogin;
			$param->strPassword  = $this->apiPasswd;
			$param->arContactIds = (array) $contacts;
			$param->strGroupId   = $group;
			$this->lastResult    = $this->soapClient->AddContactsToGroup($param);
			
			if ($this->apiError()) {
				return false;
			} else {
				if (empty($this->lastResult->AddContactsToGroupResult->Data)) {
					return false;
				} else {
					return $this->lastResult->AddContactsToGroupResult->Data;
				}
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function deleteContacts($contacts) {
		try {
			$this->soapClient    = new SoapClient($this->apiUrlContacts);
			$param               = null;
			$param->strUserName  = $this->apiLogin;
			$param->strPassword  = $this->apiPasswd;
			$param->arContactIds = (array) $contacts;
			$this->lastResult    = $this->soapClient->DeleteContacts($param);
			
			if ($this->apiError()) {
				return false;
			} else {
				return true;
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function moveContactToArchive($contact) {
		try {
			$this->soapClient     = new SoapClient($this->apiUrlContacts);
			$param                = null;
			$param->strUserName   = $this->apiLogin;
			$param->strPassword   = $this->apiPasswd;
			$param->strContactId  = $contact;
			$param->strReasonCode = 'UNREGISTERED';
			$this->lastResult     = $this->soapClient->MoveContactToArchive($param);
			
			if ($this->apiError()) {
				return false;
			} else {
				return true;
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function getGroups() {
		try {
			$this->soapClient   = new SoapClient($this->apiUrlGroups);
			$param              = null;
			$param->strUserName = $this->apiLogin;
			$param->strPassword = $this->apiPasswd;
			$this->lastResult   = $this->soapClient->GetAllGroups($param);

			if ($this->apiError()) {
				return false;
			} else {
				if (empty($this->lastResult->GetAllGroupsResult->DataArray->GroupData)) {
					return array();
				} else {
					return $this->lastResult->GetAllGroupsResult->DataArray->GroupData;
				}
			}
        
		} catch(Exception $e) {
			$this->soapError($e);
			return false;
		}
    }

    function apiError() {
	
		$var = key($this->lastResult);
		
		if (empty($this->lastResult->$var)) {
			$this->errorMessage = 'No result object found';
			$this->errorCode    = 'API';
			return true;
		}
		
		$result = $this->lastResult->$var;
		
		if ($result->Code !== 0) {
			$this->errorMessage = empty($result->Description) ? 'n/a' : $result->Description;
			$this->errorCode    = $result->Code;
			return true;
		}
		
		$this->errorMessage = '';
		$this->errorCode    = '';
		
		return false;
    }

    function soapError($e) {
		$this->errorMessage = $e->getMessage();
		$this->errorCode    = 'SOAP';
    }

    function getError($show_code = false) {
		$error = '';
		if ($show_code) {
			$error = $this->errorCode;
		}
		$error .= ($error ? ': ' : '') . $this->errorMessage;
		return $error;
    }
}
