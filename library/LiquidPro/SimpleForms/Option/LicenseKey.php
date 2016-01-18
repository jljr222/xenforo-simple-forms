<?php

class LiquidPro_SimpleForms_Option_LicenseKey
{
	/**
	 * Verifies the license key.
	 *
	 * @param string $licenseKey license key to be verified
	 * @param XenForo_DataWriter $dw Calling DW
	 * @param string $fieldName Name of field/option
	 *
	 * @return true
	 */
	public static function verifyOption(&$licenseKey, XenForo_DataWriter $dw, $fieldName)
	{
		if (XenForo_Application::getOptions()->simpleFormsLicenseKey && !self::VerifyLicense($licenseKey))
		{
			$errorMessage = 'The license key you entered is invalid. If you are having trouble with your license, please contact <a href="https://liquidpro.net/clients/clientarea.php" target="_blank">LiquidPro Support</a>.';
			$dw->error($errorMessage, $fieldName);
		}
	    
		return true;
	}
	
	/**
	 * Returns whether or not the license key is valid.
	 * @param string $licenseKey
	 * @param string $localKey
	 * @return boolean
	 */
	public static function VerifyLicense($licenseKey, $localKey = null)
	{
		$dataRegistry = new XenForo_Model_DataRegistry();
		
		if (XenForo_Application::getOptions()->simpleFormsLicenseKey == $licenseKey)
		{
			$localKey = $dataRegistry->get('lpsf_localkey');	
		}

		if ($licenseKey != '')
		{
			$licenseCheck = self::_checkLicense($licenseKey, $localKey);
			if (array_key_exists('status', $licenseCheck) && $licenseCheck['status'] == 'Active')
			{
				if (array_key_exists('localkey', $licenseCheck) && $localKey != $licenseCheck['localkey'])
				{
					$dataRegistry->set('lpsf_localkey', $licenseCheck['localkey']);
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	public static function resetLocalKey()
	{
	    $dataRegistry->set('lpsf_localkey', '');
	}
	
	/**
	 * Returns detailed information on the license key.
	 *
	 * @param string $licenseKey
	 * @param string $localKey
	 *
	 * @return string
	 */
	protected static function _checkLicense($licenseKey, $localKey = '') {
	    $licenseKey = trim($licenseKey, ' ');
	    $domain = 'liquidpro.net';
	    $directory = '/clients';
	    $licensingSecretKey = "liquidprosSuperSecretPassKeyDONOTGIVEOUT!"; // Unique value, should match what is set in the product configuration for MD5 Hash Verification
	    $localKeyDays = 15; // How long the local key is valid for in between remote checks
	    $allowCheckFailDays = 5; // How many days to allow after local key expiry before blocking access if connection cannot be made
	
	    $whmcsUrl = 'https://' . $domain . $directory . '/';
	    $checkToken = time() . md5(mt_rand(1000000000, 9999999999) . $licenseKey);
	    $checkDate = date("Ymd");
	    $usersIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
	    $localKeyValid = false;
	    if ($localKey && $localKey != '')
	    {
	        $localKey = str_replace("\n", '', $localKey); # Remove the line breaks
	        $localData = substr($localKey, 0, strlen($localKey) - 32); # Extract License Data
	        $md5hash = substr($localKey, strlen($localKey) - 32); # Extract MD5 Hash
	        if ($md5hash == md5($localData . $licensingSecretKey))
	        {
	            $localData = strrev($localData); # Reverse the string
	            $md5hash = substr($localData, 0, 32); # Extract MD5 Hash
	            $localData = substr($localData, 32); # Extract License Data
	            $localData = base64_decode($localData);
	            $localKeyResults = unserialize($localData);
	            $originalCheckDate = $localKeyResults["checkdate"];
	            if ($md5hash == md5($originalCheckDate . $licensingSecretKey))
	            {
	                $localExpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localKeyDays, date("Y")));
	                if ($originalCheckDate > $localExpiry)
	                {
	                    $localKeyValid = true;
	                    $results = $localKeyResults;
	
	                    if (isset($results['validdomain']))
	                    {
	                        $validDomains = explode(",", $results["validdomain"]);
	                        if (!in_array($_SERVER['SERVER_NAME'], $validDomains))
	                        {
	                            $localKeyValid = false;
	                            $localKeyResults["status"] = "Invalid";
	                            $results = array();
	                        }
	                    }
	
	                    if (isset($results['validip']))
	                    {
	                        $validIps = explode(",", $results["validip"]);
	                        if (!in_array($usersIp, $validIps))
	                        {
	                            $localKeyValid = false;
	                            $localKeyResults["status"] = "Invalid";
	                            $results = array();
	                        }
	                    }
	
	                    if (isset($results['validdirectory']) && $results["validdirectory"] != dirname(__FILE__))
	                    {
	                        $localKeyValid = false;
	                        $localKeyResults["status"] = "Invalid";
	                        $results = array();
	                    }
	
	                    $results["remotecheck"] = false;
	                }
	            }
	        }
	    }
	
	    if (!$localKeyValid)
	    {
	        $postFields["licensekey"] = $licenseKey;
	        $postFields["domain"] = $_SERVER['SERVER_NAME'];
	        $postFields["ip"] = $usersIp;
	        $postFields["dir"] = dirname(__FILE__);
	        	
	        if ($checkToken)
	            $postFields["check_token"] = $checkToken;
	        
	        if (function_exists("curl_exec"))
	        {
	            $ch = curl_init();
	            curl_setopt($ch, CURLOPT_URL, $whmcsUrl."modules/servers/licensing/verify.php");
	            curl_setopt($ch, CURLOPT_POST, 1);
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
	            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	            $data = curl_exec($ch);

	            curl_close($ch);
	        }
	        else
	        {
	            $fp = fsockopen('ssl://' . $domain, 443, $errno, $errstr, 5);
                if ($fp)
                {
                    $querystring = http_build_query($postFields);
                    	
                    $header = "POST " . $directory . "/modules/servers/licensing/verify.php HTTP/1.0\r\n";
                    $header .= "Host: " . $domain . "\r\n";
                    $header .= "Content-type: application/x-www-form-urlencoded\r\n";
                    $header .= "Content-length: " . @strlen($querystring) . "\r\n";
                    $header .= "Connection: close\r\n\r\n";
                    $header .= $querystring;
                    	
                    $data = "";
                    	
                    @stream_set_timeout($fp, 20);
                    @fputs($fp, $header);
                    $status = @socket_get_status($fp);
                    	
                    while (!@feof($fp) && $status)
                    {
                        $data .= @fgets($fp, 1024);
                        $status = @socket_get_status($fp);
                    }
                    @fclose ($fp);
                } 
	        }
	        
	        if (!$data)
	        {
	            $localExpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localKeyDays + $allowCheckFailDays), date("Y")));
	
	            if (isset($localKeyResults) && $originalCheckDate > $localExpiry)
	            {
	                $results = $localKeyResults;
	            }
	            else
	            {
	                $results["status"] = "Invalid";
	                $results["description"] = "Remote Check Failed";
	                return $results;
	            }
	        }
	        else
	        {
	            preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
	            $results = array();
	
	            foreach ($matches[1] AS $key => $value)
	            {
	                $results[$value] = $matches[2][$key];
	            }
	        }
	        	
	        if (isset($results["md5hash"]))
	        {
	            if ($results["md5hash"] != md5($licensingSecretKey . $checkToken))
	            {
	                $results["status"] = "Invalid";
	                $results["description"] = "MD5 Checksum Verification Failed";
	                return $results;
	            }
	        }
	        	
	        if ($results["status"]=="Active")
	        {
	            $results["checkdate"] = $checkDate;
	            $dataEncoded = serialize($results);
	            $dataEncoded = base64_encode($dataEncoded);
	            $dataEncoded = md5($checkDate . $licensingSecretKey) . $dataEncoded;
	            $dataEncoded = strrev($dataEncoded);
	            $dataEncoded = $dataEncoded . md5($dataEncoded . $licensingSecretKey);
	            $dataEncoded = wordwrap($dataEncoded, 80, "\n", true);
	            $results["localkey"] = $dataEncoded;
	        }
	        	
	        $results["remotecheck"] = true;
	    }
	    unset($postFields, $data, $matches, $whmcsUrl, $licensingSecretKey, $checkDate, $usersIp, $localKeyDays, $allowCheckFailDays, $md5hash);
	    
	    return $results;
	}	
}