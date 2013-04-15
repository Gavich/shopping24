<?php
/**
 * @category TC
 * @package TC_Varnish
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Varnish_Model_Request{

	/**
	 * Lock identifier
	 */
	const LOCK_NAME = 'TC_VARNISH_LOCK';

	/**
	 * Response code for successful request
	 */
	const RESPONSE_CODE_OK = 200;

	/**
	 * Response code to indicate that an authentication is required
	 */
	const RESPONSE_CODE_AUTH_REQUIRED = 107;

	/**
	 * Socket handle
	 * @var resource
	 */
	protected $_handler = null;

	/**
	 * Socket host
	 * @var string
	 */
	protected $_host = null;

	/**
	 * Varnish secret
	 * @var string
	 */
	protected $_secret = null;


	protected $_purge_command = null;

	protected $_purge_commands = array(2 => 'purge', 3 => 'ban');

	/**
	 * Create and object and init a connection
	 * @param array $server
	 * @throws Exception
	 */
	public function __construct($server){
		if (!isset($server['host']) || !isset($server['port'])){
			throw new Exception(
				Mage::helper('tc_varnish')->__('Parameters are invalid')
			);
		}
		$host = $server['host'];
		$port = $server['port'];
		$this->_handler = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($this->_handler === false){
			throw new Exception(
				Mage::helper('tc_varnish')->__('Could not create a socket')
			);
		}

		$result = socket_connect($this->_handler, $host, $port);
		if ($result === false){
			throw new Exception(
				Mage::helper('tc_varnish')->__('Could not connect to %s:%s', $host, $port)
			);
		}
		//set timeout 5sec
		socket_set_option($this->_handler,SOL_SOCKET, SO_RCVTIMEO, array("sec" => 5, "usec" => 0));

		if (!isset($server['check_resp']) || $server['check_resp']){
			$response = $this->_popLastResponse();
			if ($response->code != self::RESPONSE_CODE_OK) {
				if ($response->code == self::RESPONSE_CODE_AUTH_REQUIRED) {
					if (!isset($server['secret'])) {
						throw new Exception(
							Mage::helper('tc_varnish')->__(
								'Authentification required at %s:%s. Secret is not set',
								$host,
								$port
							)
						);
					}
					$this->_secret = $server['secret'];
					$parts = explode("\n", $response->body);

					$challenge = $parts[0];
					$this->_authenticate($challenge);
					$authResponse = $this->_popLastResponse();
					if ($authResponse->code != self::RESPONSE_CODE_OK) {
						throw new Exception(
							Mage::helper('tc_varnish')->__(
								'Authentification failed at %s:%s',
								$host,
								$port
							)
						);
					}
				} else {
					throw new Exception(
						Mage::helper('tc_varnish')->__(
							'Could not retrive an information at %s:%s. Unknown response code %s',
							$host,
							$port,
							$response->code
						)
					);
				}
			}
		}
		$this->_host = $host;
		$this->_purge_command = $this->_purge_commands[Mage::getStoreConfig('tc_varnish/general/version')];
	}

	/**
	 * Put a command
	 * @param string $command
	 * @param bool $checkResponse
	 * @return stdClass response
	 * @throws Exception
	 */
	protected function _put($command, $checkResponse = false){
		$command .= "\n";
		if ((socket_write($this->_handler, $command, strlen($command))) === false) {
			throw new Exception(
				Mage::helper('tc_varnish')->__('Unable to send a command to %s', $this->_host)
			);
		}
		if ($checkResponse) {
			$response = $this->_popLastResponse();
			if ($response->code != self::RESPONSE_CODE_OK) {
				$message = Mage::helper('tc_varnish')->__(
					'Command "%s" failed at %s',
					trim($command, "\n"),
					$this->_host
				);
				throw new Exception($message);
			}
		}
	}

	/**
	 * Pop the last socket response
	 * @return stdClass
	 * @throws Exception
	 */
	protected function _popLastResponse(){
		$rawResponse = socket_read($this->_handler, 12 + 1);
		if ($rawResponse === false) {
			throw new Exception(
				Mage::helper('tc_varnish')->__('Error during result processing on %s', $this->_host)
			);
		}
		$params = explode(' ', trim($rawResponse));
		$rawResponse = socket_read($this->_handler, $params[1] + 1);
		if ($rawResponse === false){
			throw new Exception(
				Mage::helper('tc_varnish')->__('Error during result processing on %s', $this->_host)
			);
		}
		$response = new stdClass();
		$response->code = $params[0];
		$response->body = $rawResponse;
		return $response;
	}

	/**
	 * Send authentification request
	 * @param string $challenge
	 */
	protected function _authenticate($challenge){
		$key = $challenge."\x0A".$this->_secret."\x0A".$challenge."\x0A";
		$key = hash('sha256', $key);
		$this->_put('auth '.$key);
	}

	/**
	 * Purge pages by specific url
	 * @param string $url
	 */
	public function purgeByUrl($url){
		$this->_put($this->_purge_command.".url {$url}", true);
	}

	/**
	 * Check if connector is locked
	 * @return bool
	 */
	public function isLocked(){
		return (Mage::app()->loadCache(self::LOCK_NAME) !== false);
	}

	/**
	 * Lock connector
	 */
	public function lock(){
		Mage::app()->saveCache('LOCK', self::LOCK_NAME, array());
	}

	/**
	 * Unlock connector
	 */
	public function unlock(){
		Mage::app()->removeCache(self::LOCK_NAME);
	}

	/**
	 * Sends purge request
	 * @param string $url
	 * @throws Exception
	 * @return void(0)
	 */
	public function purge($url = '.*'){
		if ($this->isLocked()){
			throw new Exception(
				Mage::helper('tc_varnish')->__('Connector is locked')
			);
		}
		try {
			$this->lock();
			$this->purgeByUrl($url);

		}catch (Exception $e){
			$this->unlock();
			throw $e;
		}
		$this->unlock();
	}
}