<?php
/**
 * @version		$Id: LispMiner.php 1063 2013-11-24 16:17:03Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */


namespace KBI;
//require_once dirname(__FILE__).'/../KBIntegrator.php';
//require_once dirname(__FILE__).'/../IHasDataDictionary.php';

/**
 * IKBIntegrator implementation for LISp-Miner via SEWEBAR Connect web interface.
 *
 * @package KBI
 */
class LispMiner extends KBIntegrator implements IHasDataDictionary
{
	private $user = null;

	//region Getters

	public function getMethod()
	{
		return isset($this->config['method']) ? $this->config['method'] : 'POST';
	}

	public function getMinerId($default = null)
	{
		return isset($this->config['miner_id']) ? $this->config['miner_id'] : $default;
	}

	public function getMatrixName()
	{
		return isset($this->config['matrix']) ? $this->config['matrix'] : 'Loans';
	}

	public function getPort()
	{
		return isset($this->config['port']) ? $this->config['port'] : 80;
	}

	public function getPooler()
	{
		return isset($this->config['pooler']) ? $this->config['pooler'] : 'task';
	}

	public function getUser()
	{
		if ($this->user != null) {
			return $this->user;
		}

		return $this->getAnonymousUser();
	}

	public function setUser($user)
	{
		if ($user === null) {
			return;
		}

		if (is_array($user)) {
			$username = $user['username'];
			$password = $user['password'];
		} else if (is_object($user)) {
			$username = $user->username;
			$password = $user->password;
		} else {
			throw new \Exception('User not in correct format. Expecting array or object with username and password values.');
		}

		$this->user = array('username' => $username, 'password' => $password);
	}

	protected function getAnonymousUser()
	{
		return array('username' => 'anonymous', 'password' => '');
	}

	//endregion

	public function __construct($config)
	{
		parent::__construct($config);
	}

	/**
	 * @param array|\SimpleXMLElement $db_cfg
	 * @return string ID of registered miner.
	 * @throws \Exception
	 */
	public function register($db_cfg)
	{
		$client = $this->getRestClient();
		$credentials = $this->getUser();
		$data = $db_cfg;

		if (is_array($db_cfg)) {
			$request_xml = new \SimpleXMLElement("<RegistrationRequest></RegistrationRequest>");

			if (isset($db_cfg['metabase'])) {
				$request_xml_metabase = $request_xml->addChild('Metabase');
				$request_xml_metabase->addAttribute('type', 'Access');
				$request_xml_metabase->addChild('File', $db_cfg['metabase']);
			}

			if (isset($db_cfg['type'])) {
				$request_xml_connection = $request_xml->addChild('Connection');
				$request_xml_connection->addAttribute('type', $db_cfg['type']);

				if (isset($db_cfg['server'])) {
					$request_xml_connection->addChild('Server', $db_cfg['server']);
				}

				if (isset($db_cfg['database'])) {
					$request_xml_connection->addChild('Database', $db_cfg['database']);
				}

				if (isset($db_cfg['username'])) {
					$request_xml_connection->addChild('Username', $db_cfg['username']);
				}

				if (isset($db_cfg['password'])) {
					$request_xml_connection->addChild('Password', $db_cfg['password']);
				}
			} else {
				throw new \Exception('Database configuration does not specify connection type.');
			}

			$data = $request_xml->asXML();
		} else if (is_a($db_cfg, 'SimpleXMLElement')) {
			$data = $db_cfg->asXML();
		}

		$url = trim($this->getUrl(), '/');
		$url = "$url/miners";

		$response = $client->post($url, $data, $credentials);

		KBIDebug::log(array('config' => $db_cfg, 'response' => $response, 'url' => $url), "Miner registered");

		return $this->parseRegisterResponse($response);
	}

	/**
	 * @param RESTClientResponse $response
	 * @return string
	 * @throws \Exception
	 */
	protected function parseRegisterResponse($response)
	{
		$body = $response->getBodyAsXml();

		if($response->getStatusCode() != 200 || $body['status'] == 'failure') {
			throw new \Exception(isset($body->message) ? (string)$body->message : $response->getStatus());
		} else if($body['status'] == 'success') {
			return (string)$body['id'];
		}

		throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response)));
	}

	public function unregister($server_id = null)
	{
		$client = $this->getRestClient();
		$credentials = $this->getUser();

		$url = trim($this->getUrl(), '/');
		$url = "$url/miners/{$this->getMinerId($server_id)}";

		$response = $client->delete($url, null, $credentials);

		return $this->parseResponse($response, "Miner unregistered/removed.");
	}

	public function importDataDictionary($dataDictionary, $server_id = null)
	{
		$server_id = $server_id == null ? $this->getMinerId() : $server_id;

		if($server_id === null) {
			throw new \Exception('LISpMiner ID was not provided.');
		}

		$client = $this->getRestClient();
		$credentials = $this->getUser();

		$url = trim($this->getUrl(), '/');
		$url = "$url/miners/{$server_id}/DataDictionary";

		$response = $client->put($url, $dataDictionary, $credentials);

		KBIDebug::log($response, "Import executed");

		return $this->parseResponse($response);
	}

	/**
	 * @param $params
	 * @return string
	 * @throws \Exception
	 */
	public function getDataDescription($params = null)
	{
		$server_id = $this->getMinerId();

		if($server_id === null) {
			throw new \Exception('LISpMiner ID was not provided.');
		}

		$client = $this->getRestClient();
		$credentials = $this->getUser();

		$url = trim($this->getUrl(), '/');
		$url = "$url/miners/{$server_id}/DataDictionary";

		$data = array(
			'matrix' => $this->getMatrixName(),
			'template' => (@$params['template']?$params['template']:'')
		);

		KBIDebug::info(array($url, $data), "getting DataDictionary");

		$response = $client->get($url, $data, $credentials);

		if ($response->isSuccess()) {
			return trim($response->getBody());
		}

		return $this->parseResponse($response);
	}

	public function queryPost($query, $options)
	{
		// $options['export'] = '9741046ed676ec7470cb043db2881a094e36b554';
		// TODO: add user credentials to options

		$server_id = $this->getMinerId();

		if($this->getMinerId() === null) {
			throw new \Exception('LISpMiner ID was not provided.');
		}

		$url = trim($this->getUrl(), '/');

		$data = array();
		$client = $this->getRestClient();
		$credentials = $this->getUser();

		if(isset($options['template'])) {
			$data['template'] = $options['template'];
			KBIDebug::info("Using LM exporting template {$data['template']}", 'LISpMiner');
		}

		if(isset($options['export'])) {
			$task = $options['export'];
			$url = "$url/miners/{$server_id}/tasks/{$task}";

			KBIDebug::info("Making just export of task '{$task}' (no generation).", 'LISpMiner');
			KBIDebug::log(array('URL' => $url, 'GET' => $data, 'POST' => $query), 'LM Query');

			$response = $client->get("$url", $data, $credentials);
		} else {
			$pooler = $this->getPooler();

			if(isset($options['pooler'])) {
				$pooler = $options['pooler'];
				KBIDebug::info("Using '{$pooler}' as pooler", 'LISpMiner');
			}

			switch($pooler) {
				case 'grid':
					$url = "$url/miners/{$server_id}/tasks/grid";
				break;
				case 'proc':
					$url = "$url/miners/{$server_id}/tasks/proc";
					break;
				case 'task':
				default:
					$url = "$url/miners/{$server_id}/tasks/task";
			}

			KBIDebug::log(array('URL' => $url, 'GET' => $data, 'POST' => $query), 'LM Query');

			$response = $client->post("$url?{$client->encodeData($data)}", $query, $credentials);
		}

		if ($response->isSuccess()) {
			return $response->getBody();
		}

		return $this->parseResponse($response);
	}

	public function cancelTask($taskName)
	{
		$request_xml = new \SimpleXMLElement("<CancelationRequest></CancelationRequest>");
		$server_id = $this->getMinerId();

		if($this->getMinerId() === null) {
			throw new \Exception('LISpMiner ID was not provided.');
		}

		$url = trim($this->getUrl(), '/');
		$client = $this->getRestClient();
		$credentials = $this->getUser();

		switch($this->getPooler()) {
			case 'grid':
				$url = "$url/miners/{$server_id}/tasks/grid/{$taskName}";
				break;
			case 'proc':
				$url = "$url/miners/{$server_id}/tasks/proc/{$taskName}";
			break;
			case 'task':
			default:
				$url = "$url/miners/{$server_id}/tasks/task/{$taskName}";
		}

		KBIDebug::info(array($url), 'Canceling task');

		$response = $client->put($url, $request_xml->asXML(), $credentials);

		if ($response->isSuccess()) {
			return $response->getBody();
		}

		return $this->parseResponse($response);
	}

	public function test()
	{
		try {
			$server_id = $this->getMinerId();

			if($server_id === null) {
				throw new \Exception('LISpMiner ID was not provided.');
			}

			$client = $this->getRestClient();
			$credentials = $this->getUser();

			$url = trim($this->getUrl(), '/');
			$url = "$url/miners/{$server_id}";

			$response = $client->get($url, null, $credentials);

			KBIDebug::log($response, "Test executed");

			$this->parseResponse($response, '');

			return true;
		}
		catch (\Exception $ex)
		{
			return false;
		}
	}

	//region SewebarKey

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @return mixed
	 */
	public function registerUser($username, $password, $email = '')
	{
		// TODO: credentials for given user (from session)
		$client = $this->getRestClient();
		$url = trim($this->getUrl(), '/');
		$url = "$url/users/${username}";

		$data = array(
			'name' => $username,
			'password' => $password,
			'email' => $email
		);

		$response = $client->post($url, $data);

		KBIDebug::log(array('url' => $url, 'data' => $data, 'response' => $response), "User registered");

		return $this->parseResponse($response, 'User successfully registered.');
	}

    /**
     * @param string $username
     * @param string $password
     * @param string $new_username
     * @param string $new_password
     * @param string $new_email
     * @throws \Exception
     * @return mixed
     */
    public function updateUser($username, $password, $new_username, $new_password, $new_email)
    {
        if (empty($username)) {
            throw new \Exception('Username cannot be empty!');
        }

        if (empty($password)) {
            throw new \Exception('Password cannot be empty!');
        }

        $credentials = array(
            'username' => $username,
            'password' => $password
        );

        $client = $this->getRestClient();
        $url = trim($this->getUrl(), '/');
        $url = "$url/Users";

        $data = array(
            'name' => $username,
            'password' => $password,
            'new_name' => $new_username,
            'new_password' => $new_password,
            'new_email' => $new_email
        );

        $response = $client->put($url, $data, $credentials);

        KBIDebug::log(array('url' => $url, 'data' => $data, 'response' => $response), "User updated");

        return $this->parseResponse($response, 'User successfully updated.');
    }

	/**
	 * @param string $username
	 * @param string $new_username
	 * @param string $new_password
	 * @param string $new_email
	 * @param string $email_from - e-mail address for "From:" field for confirmation e-mail
	 * @param string $email_link - link for user confirmation / string {code} should be on server replaced with security code value
     *
     * @return string
     *
     * @throws \Exception
	 */
	public function updateOtherUser($username, $new_username, $new_password, $new_email, $email_from, $email_link)
	{
        $client = $this->getRestClient();
        $url = trim($this->getUrl(), '/');
        $url = "$url/users/${username}/changes";

        $data = array(
            'new_username' => $new_username,
            'new_password' => $new_password,
            'new_email' => $new_email,
            'email_from' => $email_from,
            'email_link' => $email_link
        );

        $response = $client->post($url, $data);

        return $this->parseResponse($response);
	}

    /**
     * @param string $username
     * @param string $security_code
     *
     * @return string
     *
     * @throws \Exception
     */
    public function confirmUserPasswordUpdate($username, $security_code)
    {
        $client = $this->getRestClient();
        $url = trim($this->getUrl(), '/');
        $url = "$url/users/${username}/changes";

        $data = array(
            'code' => $security_code
        );

        $response = $client->put($url, $data);

        return $this->parseResponse($response);
    }

	/**
	 * Removes existing user.
	 * @param $username string User to remove
	 * @param $admin_password string Password for authorized user (admin or user itself).
	 * @param $admin_username string Authorized user to remove this user (admin or user itself).
	 * @return string
	 * @throws \Exception
	 */
	public function deleteUser($username, $admin_password, $admin_username = null)
	{
		if (empty($username)) {
			throw new \Exception('Username cannot be empty!');
		}

		$credentials = array(
			'username' => empty($admin_username) ? $username : $admin_username,
			'password' => $admin_password
		);

		$client = $this->getRestClient();
		$url = trim($this->getUrl(), '/');
		$url = "$url/users/${username}";

		$response = $client->delete($url, null, $credentials);

		KBIDebug::log(array('url' => "DELETE $url", 'response' => $response), "User removed");

		return $this->parseResponse($response, 'User successfully removed.');
	}

	/**
	 * Creates user's database.
	 *
	 * @param string $username
	 * @param string $password
	 * @param int $db_id
	 * @param string $db_password
	 * @return mixed
	 */
	public function registerUserDatabase($username, $password, $db_id, $db_password)
	{
		if (empty($username)) {
			$anonymous = $this->getAnonymousUser();
			$username = $anonymous['username'];
			$password = $anonymous['password'];
		}

		$client = $this->getRestClient();
		$url = trim($this->getUrl(), '/');
		$url = "$url/users/$username/databases";

		$credentials = array('username' => $username, 'password' => $password);

		$data = array(
			'db_id' => $db_id,
			'db_password' => $db_password
		);

		$response = $client->post($url, $data, $credentials);

		KBIDebug::log(array('url' => $url, 'data' => $data, 'response' => $response), "User's database registered");

		return $this->parseResponse($response, "User's database successfully registered.");
	}

	/**
	 * Removes user's database.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $db_id
	 * @return mixed
	 */
	public function unregisterUserDatabase($username, $password, $db_id)
	{
		if (empty($username)) {
			$anonymous = $this->getAnonymousUser();
			$username = $anonymous['username'];
			$password = $anonymous['password'];
		}

		$client = $this->getRestClient();
		$url = trim($this->getUrl(), '/');
		$url = "$url/users/$username/databases/{$db_id}";

		$response = $client->delete($url, null, array('username' => $username, 'password' => $password));

		KBIDebug::log(array('url' => $url, 'response' => $response), "User's database removed");

		return $this->parseResponse($response, "User's database successfully unregistered.");
	}

	/**
	 * Retrieves user's database's password.
	 *
	 * @param string $username
	 * @param string $password
	 * @param int $db_id
	 * @return string
	 */
	public function getDatabasePassword($username, $password, $db_id)
	{
		if (empty($username)) {
			$anonymous = $this->getAnonymousUser();
			$username = $anonymous['username'];
			$password = $anonymous['password'];
		}

		$client = $this->getRestClient();
		$url = trim($this->getUrl(), '/');
		$url = "$url/users/$username/databases/$db_id";

		$response = $client->get($url, null, array('username' => $username, 'password' => $password));

		KBIDebug::log(array('url' => $url, 'response' => $response), "Password retring");

		return $this->parseDatabasePassword($response);
	}

	public function setDatabasePassword($username, $password, $db_id, $old_password, $new_password)
	{
		$client = $this->getRestClient();
		$url = trim($this->getUrl(), '/');
		$url = "$url/users/$username/databases/$db_id";

		$data = array(
			'db_id' => $db_id,
			'db_password' => $new_password
		);

		$response = $client->put($url, $data, array('username' => $username, 'password' => $password));

		KBIDebug::log(array('url' => "PUT $url", 'data' => $data, 'response' => $response), "Password changed");

		return $this->parseResponse($response, "User's datbases password changed successfully.");
	}

	/**
	 * @param RESTClientResponse $response
	 * @return string
	 * @throws \Exception
	 */
	protected function parseDatabasePassword($response)
	{
		$body = $response->getBodyAsXml();

		if(!$response->isSuccess() || $body['status'] == 'failure') {
			throw new \Exception(isset($body->message) ? (string)$body->message : $response->getStatus());
		} else if($body['status'] == 'success') {
			return (string)$body->database['password'];
		}

		throw new \Exception('Response not in expected format');
	}

	//endregion

	/**
	 * @param RESTClientResponse $response
	 * @param $message
	 * @return string
	 * @throws \Exception
	 */
	protected function parseResponse($response, $message = '')
	{
		$body = $response->getBodyAsXml();

		if(!$response->isSuccess() || $body['status'] == 'failure') {
			throw new \Exception(isset($body->message) ? (string)$body->message : $response->getStatus());
		} else if($body['status'] == 'success') {
			return isset($body->message) ? (string)$body->message : $message;
		}

		throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response->getBody())));
	}
}