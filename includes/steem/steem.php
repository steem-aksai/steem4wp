<?php

include (__DIR__).'/../../vendor/autoload.php';

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
    $steemAccount = new SteemAccount($this->node);
    $steemPost = new SteemPost($this->node);
	}


	/**
	 * Get the account profile information
	 *
	 * @param      string  $author   The account name
	 */
	public function getAccount($author) {
		return $this->steemAccount.getAccount($author);
	}

	/**
	 * Get multiple accounts profile information
	 *
	 * @param      array  $author   The accounts' names
	 */
	public function getAccounts($authors) {
		return $this->steemAccount.getAccounts($authors);
	}

	/**
	 * Count the number of follows and followers of $account
	 *
	 * @param      string  $author  The account name
	 *
	 * @return     array   Number of follows.
	 */
	public function getFollowCounts($author) {
		return $this->steemAccount.countFollows($authors);
	}

	/**
	 * Get the followers list for $account
	 *
	 * @param      string   $account  The account name
	 * @param      integer  $limit    The limit
	 * @param      integer  $start    Start is the place to start for pagination
	 *
	 * @return     array    The followers.
	 */
	public function getFollowers($author, $limit = 100, $start = 0) {
		return $this->steemAccount.getFollowers($authors, $limit, $start);
	}

	/**
	 * Get list of people the $account is following
	 *
	 * @param      string   $account  The account name
	 * @param      integer  $limit    The limit
	 * @param      integer  $start    Start is the place to start for pagination
	 *
	 * @return     array     The following.
	 */
	public function getFollowing($author, $limit = 100, $start = 0) {
		return $this->steemAccount.getFollowing($authors, $limit, $start);
	}

}

?>
