<?php
	/**
	 * @package     Joomla.Plugin
	 * @subpackage  Authentication.External
	 *
	 * @copyright   Copyright (C) 2014 Stefan Herzog. All rights reserved.
	 * @license     GNU General Public License version 2 or later; see LICENSE.txt
	 */
	
	defined('_JEXEC') or die;
	
	/**
	 * External Authentication Plugin
	 *
	 * @package     Joomla.Plugin
	 * @subpackage  Authentication.external
	 * @since       3.1
	 */

	// no direct access
	defined( '_JEXEC' ) or die( 'Restricted access' );
	class PlgAuthenticationExternal extends JPlugin
	{
        /**
         * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
         * If you want to support 3.0 series you must override the constructor
         *
         * @var    boolean
         * @since  3.1
         */
        protected $autoloadLanguage = true;
		/**
			 * This method should handle any authentication and report back to the subject
			 *
			 * @param   array   $credentials  Array holding the user credentials
			 * @param   array   $options      Array of extra options
			 * @param   object  &$response    Authentication response object
			 * @param	object	$response	  Authentication response object
		 	 * @param	array 	$result		  Authenticataion response array	
			 * @return  boolean
			 *
			 * @since   1.5
			 */
			public function onUserAuthenticate($credentials, $options, & $response)
			{
					
				// Set default values
				$backend = false;	
					
					
					
				// For JLog
				$response->type = 'EXTERNAL';

				// External does not like blank passwords
				if (empty($credentials['password']))
				{
					$response->status = JAuthentication::STATUS_FAILURE;
					$response->error_message = JText::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');
		
					return false;
				}
				
				// Include additional script files
				require_once('functions.php');
				require_once('stuff.php');
			
				// Load plugins parameters from config in backend
				$external_protocol		= $this->params->get('protocol');
				$external_host			= $this->params->get('host');
				$external_script		= $this->params->get('scriptname');
				$external_table			= $this->params->get('tablename');
				
				$external_backend_radio	= $this->params->get('radio_backend_access');
				$external_backend		= $this->params->get('field_backend_access');
				$external_backend_value	= $this->params->get('value_backend_access');
				$external_backend_group	= $this->params->get('group_backend_access');

				$external_username		= $this->params->get('field_username');
				$external_password		= $this->params->get('field_password');
				$external_first_name	= $this->params->get('field_first_name');
				$external_last_name		= $this->params->get('field_last_name');
				$external_email			= $this->params->get('field_email');

				$external_salt			= $this->params->get('field_salt');
				$external_pepper		= $this->params->get('pepper');
				$external_encryption	= $this->params->get('encryption');

				$external_php_user		= $this->params->get('phpuser');
				$external_php_pw		= $this->params->get('phpPW');
				
				$external_htaccess_user	= $this->params->get('htaccessuser');
				$external_htaccess_pw	= $this->params->get('htaccessPW');				
				
				$db			= $this->db;

				
				// Check if the external script is secured by .htaccess
				if($external_htaccess_user == 'NULL')
				{
					$external_url = $external_protocol . $external_host . $external_script;
				}
				else
				{
					$external_url = $external_protocol . $external_htaccess_user . ':' . $external_htaccess_pw . '@' . $external_host . $external_script;
				}

				// Check if backend access is enabled and correctly configured
				if($external_backend_radio === '1')
				{
					if($external_backend != ''
						AND $external_backend_value != ''
						AND $external_backend_group != ''
						)
					{
						$post_backend_access = true;
					}
				}
				
				
				$user_data[$external_username]		= $credentials['username'];
				$user_data[$external_password]		= $credentials['password'];
				$user_data['table_name']			= $external_table;
				
				if($post_backend_access)
				{
					$user_data['radio_backend_access']	= $external_backend_radio;
					$user_data['field_backend_access']	= $external_backend;
					$user_data['value_backend_access']	= $external_backend_value;
					$user_data['group_backend_access']	= $external_backend_group;							
				}
				$user_data['field_username']		= $external_username;
				$user_data['field_password']		= $external_password;
					
				$user_data['field_first_name']		= $external_first_name;
				$user_data['field_last_name']		= $external_last_name;
				$user_data['field_email']			= $external_email;
				if($external_salt != '')
				{
					$user_data['field_salt']			= $external_salt;
				}

				$user_data['php_user']				= $external_php_user;
				$user_data['php_pw']				= $external_php_pw;
				$user_data['token']					= generateRandomString(32);
				$user_data['url']					= $external_url;

mail('stefan.herzog@die-kerwe.de','DEBUG ' . __LINE__,print_r($user_data,true));

				$pw = $user_data[$external_password];
				
				// Read out the encryption function from plugins configuration and write it into a file
				$func = "<?php " . $external_encryption . " ?>";
				$filename = 'tmp/tmp_func.php';
				$file = fopen($filename,'w+');
				fwrite($file,$func);
				fclose($file);

				// Include the created file which contains the encryption function
				require_once($filename);
				$user_data['password'] = encryptPassword($pw);
				
				// Delete the file
				unlink($filename);
				
				foreach($user_data as $key=>$value)
				{
					$user_data_string .= $key.'='.$value.'&';
				}
				rtrim($user_data_string, '&');

				// Send request to the external script
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $external_url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
				curl_setopt($ch, CURLOPT_POST, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, $user_data_string); 
				$external_result = curl_exec($ch); 
				curl_close($ch); 
mail('stefan.herzog@die-kerwe.de','DEBUG ' . __LINE__,print_r($external_result,true));
				// Lookup the error (or success) message
				foreach($resID AS $key=>$value)
				{
					if(substr($external_result,0,7) === $key)
					{
						$response->error_message = JText::_($value);
						$breakLoop = TRUE;
						if(strlen($external_result) >> strlen($key))
						{
							$result = substr($external_result,7);
							if(substr($result,0,8) === '_xyzabc_')
							{
								$result = substr($result,8);
								parse_str($result,$result_array);
							}
						}
					}
					else
					{
						$response->error_message = JText::_('PLG_EXTERNAL_AUTH_ERROR_PROBLEM');
						$response->error_message .= ' (Code ' . __LINE__ . ')';
					}
					if($breakLoop)
					{
						break;
					}
				}

				if(count($result_array) >> 1)
				{
					if($result_array['token'] === $user_data['token'])
					{
						$response->error_message = '';
						$response->fullname = $result_array[$external_first_name] . ' ' . $result_array[$external_last_name];
						$response->email = $result_array[$external_email];
						$response->username = $user_data[$external_username];
						$response->password = $user_data[$external_password];
						$response->status = JAuthentication::STATUS_SUCCESS;
						return true;
					}
					else
					{
						$response->status = JAuthentication::STATUS_FAILURE;
						return false;
					}
				}
				else
				{
					$response->status = JAuthentication::STATUS_FAILURE;
					return false;
				}	
			}
		}
?>