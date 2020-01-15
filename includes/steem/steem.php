<?php

use SteemPHP\SteemAccount;
use SteemPHP\SteemPost;

class Steem
{

	/**
	 * @var $node
	 *
	 * $node will be the Steem node that we connect to
	 */
  protected $node;

  	/**
	 * @var $steemAccount
	 *
	 * The SteemAccount instance
	 */
  protected $steemAccount;


  	/**
	 * @var $node
	 *
	 * The SteemPost instance
	 */
  protected $steemPost;

	/**
	 * Initialize the connection to the host
	 *
	 * @param      string  $host   The node you want to connect
	 */
	public function __construct($node = 'https://anyx.io')
	{
		$this->node = trim($node);
    $steemAccount = SteemAccount($this->node);
    $steemPost = SteemPost($this->node);
	}


}

?>
