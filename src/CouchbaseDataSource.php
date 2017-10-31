<?php
/**
 * Encapsulates bucket information.
 */
class BucketInformation {
	private $name;
	private $password;
	
	public function __construct($name, $password) {
		$this->name = $name;
		$this->password = $password;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getPassword() {
		return $this->password;
	}
}

/**
 * Encapsulates couchbase server connection & bucket data.
*/
class CouchbaseDataSource extends NoSQLDataSource  {
	private $strHost;
	private $intPort;
	private $strUserName;
	private $strPassword;
	
	private $objBucketInfo;
	
	/**
	 * Sets database server host name
	 *
	 * @param string $strHost
	 * @return void
	 */
	public function setHost($strHost) {
		$this->strHost = $strHost;
	}
	
	/**
	 * Gets database server host name
	 *
	 * @return string
	 */
	public function getHost() {
		return $this->strHost;
	}
	
	/**
	 * Sets database server port
	 *
	 * @param integer $intPort
	 * @return void
	 */
	public function setPort($intPort) {
		$this->intPort = $intPort;
	}
	
	/**
	 * Gets database server port
	 *
	 * @return integer
	 */
	public function getPort() {
		return $this->intPort;
	}
	
	/**
	 * Set bucket information.
	 * 
	 * @param string $strName Name of bucket.
	 * @param string $strPassword Password of bucket.
	 */
	public function setBucketInfo($strName, $strPassword="") {
		$this->objBucketInfo = new BucketInformation($strName, $strPassword);
	}
	
	/**
	 * Gets bucket information.
	 * 
	 * @return BucketInformation
	 */
	public function getBucketInfo() {
		return $this->objBucketInfo;
	}

	/**
	 * Sets server user name
	 *
	 * @param string $strUserName
	 * @return void
	 */
	public function setUserName($strUserName){
		$this->strUserName = $strUserName;
	}

	/**
	 * Gets server user name
	 *
	 * @return string
	 */
	public function getUserName() {
		return $this->strUserName;
	}

	/**
	 * Sets server user password
	 *
	 * @param string $strPassword
	 * @return void
	 */
	public function setPassword($strPassword) {
		$this->strPassword = $strPassword;
	}

	/**
	 * Gets server user password
	 *
	 * @return string
	 */
	public function getPassword() {
		return $this->strPassword;
	}
}