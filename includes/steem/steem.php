<?php

// namespace Steem4WP;

include (__DIR__).'/../../vendor/autoload.php';

use SteemPHP\SteemAccount;
use SteemPHP\SteemPost;
use SteemPHP\SteemChain;

if (!function_exists('write_log')) {

	function write_content($content) {
		file_put_contents('debug.log', $content . "\n", FILE_APPEND);
	}

	function write_log($log) {
		if (is_array($log) || is_object($log)) {
			write_content(print_r($log, true));
		} else {
			write_content($log);
		}
	}
}

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
    $this->steemAccount = new SteemAccount($this->node);
		$this->steemPost = new SteemPost($this->node);
		$this->steemChain = new SteemChain($this->node);
	}

	/**
	 * Get the registered Wif information of the account
	 *
	 * @param      string  $account   The account name
	 */
	protected function getWif($account)
	{
		$wif = "";
		if (function_exists('get_option')) {
			$wif = get_option("steem_dapp_wif");
		}
		if (empty($wif)) {
			$wif = getenv('STEEM_DAPP_WIF');
		}
		return $wif;
	}


	/**
	 * Get the account profile information
	 *
	 * @param      string  $author   The account name
	 */
	public function getAccount($author)
	{
		$accounts = $this->steemAccount->getAccount($author);
		if (!empty($accounts) && count($accounts) > 0) {
			return $accounts[0];
		} else {
			return null;
		}
	}

	/**
	 * Get multiple accounts profile information
	 *
	 * @param      array  $author   The accounts' names
	 */
	public function getAccounts($authors)
	{
		return $this->steemAccount->getAccounts($authors);
	}

	/**
	 * Count the number of follows and followers of $account
	 *
	 * @param      string  $author  The account name
	 *
	 * @return     array   Number of follows.
	 */
	public function getFollowsCount($author)
	{
		return $this->steemAccount->countFollows($author);
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
	public function getFollowers($author, $limit = 100, $start = 0)
	{
		return $this->steemAccount->getFollowers($author, $limit, $start);
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
	public function getFollowing($author, $limit = 100, $start = 0)
	{
		return $this->steemAccount->getFollowing($author, $limit, $start);
	}

	/**
	 * Follow an account
	 *
	 * @param    string  $follower       The follower account
	 * @param    string  $following      The following account
	 *
	 * @return     array     The following.
	 */
	public function follow($follower, $following)
	{
		return $this->steemAccount->follow($this->getWif($follower), $follower, $following);
	}

	/**
	 * Unfollow an account
	 *
	 * @param    string  $follower       The follower account
	 * @param    string  $following      The following account
	 *
	 * @return     array     The following.
	 */
	public function unfollow($follower, $following)
	{
		return $this->steemAccount->unfollow($this->getWif($follower), $follower, $following);
	}

	/**
	 * Gets the content of an article.
	 *
	 * @param      string  $author    The author
	 * @param      string  $permlink  The permlink
	 *
	 * @return     array   The content.
	 */
	public function getPostContent($author, $permlink)
	{
		return $this->steemPost->getContent($author, $permlink);
	}

	/**
	 * Gets the content replies.
	 *
	 * @param      string  $author    The author
	 * @param      string  $permlink  The permlink
	 *
	 * @return     array   The content replies.
	 */
	public function getPostReplies($author, $permlink)
	{
		return $this->steemPost->getContentReplies($author, $permlink);
	}

	/**
	 * Get list of articles written/reblogged by the author $author
	 * $startPermlink are null by default and the data can be used for pagination
	 *
	 * @param      string   $author         The author
	 * @param      integer  $limit          The limit
	 * @param      string   $startPermlink  The start permlink
	 *
	 * @return     array    The posts by the account.
	 */
	public function getPostsByAuthor($author, $limit = 100, $startPermlink = null)
	{
		return $this->steemPost->getDiscussionsByBlog($author, $limit, $startPermlink);
	}

	/**
	 * Get list of articles in the feed section for the author $author
	 * Start author and start permlink are for pagination
	 *
	 * @param      string   $author         The author
	 * @param      integer  $limit          The limit
	 * @param      string   $startAuthor    The start author
	 * @param      string   $startPermlink  The start permlink
	 *
	 * @return     array    The discussions by feed.
	 */
	public function getPostsByFeed($author, $limit = 100, $startAuthor = null, $startPermlink = null)
	{
		return $this->steemPost->getDiscussionsByFeed($author, $limit, $startAuthor, $startPermlink);
	}


	/**
	 * Gets the list of articles created under the $tag
	 * Start author and start permlink are for pagination.
	 *
	 * @param      string   $tag            The tag
	 * @param      integer  $limit          The limit
	 * @param      string   $startAuthor    The start author
	 * @param      string   $startPermlink  The start permlink
	 *
	 * @return     array    The list of articles.
	 */
	public function getPostsByCreated($tag, $limit = 100, $startAuthor = null, $startPermlink = null)
	{
		return $this->steemPost->getDiscussionsByCreated($tag, $limit, $startAuthor, $startPermlink);
	}

	/**
	 * Gets the list of trending articles (content/votes/replies) posted under the $tag.
	 * Start author and start permlink are for pagination.
	 *
	 * @param      string   $tag            The tag
	 * @param      integer  $limit          The limit
	 * @param      string   $startAuthor    The start author
	 * @param      string   $startPermlink  The start permlink
	 *
	 * @return     array    The list of trending articles.
	 */
	public function getPostsByTrending($tag, $limit = 100, $startAuthor = null, $startPermlink = null)
	{
		return $this->steemPost->getDiscussionsByTrending($tag, $limit, $startAuthor, $startPermlink);
	}

	/**
	 * Get list of articles which are hot and using tha tag $tag
	 * Start author and start permlink are for pagination
	 *
	 * @param      string   $tag            The tag
	 * @param      integer  $limit          The limit
	 * @param      string   $startAuthor    The start author
	 * @param      string   $startPermlink  The start permlink
	 *
	 * @return     array    The discussions by hot.
	 */
	public function getPostsByHot($tag, $limit = 100, $startAuthor = null, $startPermlink = null)
	{
		return $this->steemPost->getDiscussionsByHot($tag, $limit, $startAuthor, $startPermlink);
	}

	/**
	 * Create a post
	 *
	 * @param      string  $author   				The author of the comment
	 * @param      string  $title   				The title of the comment
	 * @param      string  $body   					The body of the comment
	 * @param      string  $tags   					The tags for the comment
	 * @param      string  $app             The app name for the comment
	 * @param      string  $jsonMetadata   	The json metadata of the comment
	 * @param      string  $permlink   			The permlink of the comment
	 *
	 * @return     array   The response of the action
	 */
	public function createPost($author, $title, $body, $tags = null, $app = null, $jsonMetadata = null, $permlink = null)
	{
		if (empty($tags) && function_exists('get_option')) {
			$tagsText = get_option("steem_dapp_default_tags");
			if (!empty($tagsText)) {
				$tagsText = strtolower($tagsText);
				$tags = wp_parse_list($tagsText);
			}
		}
		if (empty($jsonMetadata)) {
			$jsonMetadata = [
				"tags" => !empty($tags) ? $tags : ["cn"],
				"app" => !empty($app) ? $app : "steem4wp/1.0"
			];
		}
		if (empty($permlink)) {
			$timestamp = \DateTime::createFromFormat('U.u', microtime(true))->format("Ymd\\tHisv\z");
			$permlink = sanitizePermlink($title);
			if (empty(preg_replace("/-/", "", $permlink))) {
				$permlink = $timestamp;
			} else {
				$permlink = $permlink . '-' . $timestamp;
			}
		}
		$body = sanitizeBody($body);
		$category = $jsonMetadata["tags"][0];
		$parentPermlink = sanitizePermlink($category);
		$parentAuthor = "";

		return $this->steemPost->comment($this->getWif($author), $parentAuthor, $parentPermlink, $author, $permlink, $title, $body, $jsonMetadata);
	}

	/**
	 * Reply to a post
	 *
	 * @param      string  $parentAuthor   	The author of the parent comment
	 * @param      string  $parentPermlink  The permlink of the parent comment
	 * @param      string  $author   				The author of the comment
	 * @param      string  $body   					The body of the comment
	 * @param      string  $tags   					The tags for the comment
	 * @param      string  $app             The app name for the comment
	 * @param      string  $jsonMetadata   	The json metadata of the comment
	 *
	 * @return     array   The response of the action
	 */
	public function replyToPost($parentAuthor, $parentPermlink, $author, $body, $tags = null, $app = null, $jsonMetadata = null)
	{
		if (!$jsonMetadata) {
			$jsonMetadata = [
				"tags" => !empty($tags) ? $tags : ["cn"],
				"app" => !empty($app) ? $app : "steem4wp/1.0"
			];
		}
		$body = sanitizeBody($body);
		return $this->steemPost->comment($this->getWif($author), $parentAuthor, $parentPermlink, $author, null, "", $body, $jsonMetadata);
	}

	/**
	 * Delete the post
	 *
	 * @param      string  $author   				The author of the comment
	 * @param      string  $permlink   			The permlink of the comment
	 *
	 * @return   array    The response message
	 */
	public function deletePost($author, $permlink)
	{
		return $this->steemPost->deleteComment($this->getWif($author), $author, $permlink);
	}

	/**
	 * Upvote a post
	 *
	 * @param      string  $voter   	The account of the voter
	 * @param      string  $author   				The author of the comment
	 * @param      string  $permlink   			The permlink of the comment
	 * @param      string  $weight   				The voting weight, range: (0, 100]
	 *
	 * @return     array   The response of the action
	 */
	public function upvotePost($voter, $author, $permlink, $weight)
	{
		if ($weight > 0) {
			return $this->steemPost->vote($this->getWif($voter), $voter, $author, $permlink, $weight);
		} else {
			throw new \Exception("The upvote weight should > 0");
		}
	}

	/**
	 * Downvote a post
	 *
	 * @param      string  $voter   	The account of the voter
	 * @param      string  $author   				The author of the comment
	 * @param      string  $permlink   			The permlink of the comment
	 * @param      string  $weight   				The voting weight, range: (0, 100]
	 *
	 * @return     array   The response of the action
	 */
	public function downvotePost($voter, $author, $permlink, $weight)
	{
		if ($weight < 0) {
			return $this->steemPost->vote($this->getWif($voter), $voter, $author, $permlink, $weight);
		} else {
			throw new \Exception("The downvote weight should < 0");
		}
	}

	/**
	 * UnVote a post
	 *
	 * @param      string  $voter   	The account of the voter
	 * @param      string  $author   				The author of the comment
	 * @param      string  $permlink   			The permlink of the comment
	 *
	 * @return     array   The response of the action
	 */
	public function unvotePost($voter, $author, $permlink)
	{
		return $this->steemPost->unvote($this->getWif($voter), $voter, $author, $permlink);
	}

	/**
	 * Forward a post
	 *
	 * @param      string  $account         The account who reblogs the post
	 * @param      string  $author   				The author of the comment
	 * @param      string  $permlink   			The permlink of the comment
	 *
	 * @return   array    The response message
	 */
	public function forwardPost($account, $author, $permlink)
	{
		return $this->steemPost->reblog($this->getWif($account), $account, $author, $permlink);
	}

	/**
	 * Get Current Median History Price
	 * @return array
	 */
	public function getCurrentMeidanHistoryPrice()
	{
		return $this->steemChain->getCurrentMeidanHistoryPrice();
	}

}

function sanitizePermlink($permlink) {
	$permlink = trim($permlink);
	$permlink = preg_replace("/_|\s|\./", "-", $permlink);
	$permlink = preg_replace("/[^\w-]/", "", $permlink);
	$permlink = preg_replace("/[^a-zA-Z0-9-]/", "", $permlink);
	$permlink = strtolower($permlink);
	return $permlink;
}

function sanitizeBody($body) {
	$body = preg_replace("/<section[^>]*>/", "", $body);
	$body = preg_replace("/<\/section>/", "", $body);
	$body = preg_replace("/<span[^>]*>/", "", $body);
	$body = preg_replace("/<\/span>/", "", $body);
	$body = preg_replace("/\t/", "    ", $body);
	$body = trim($body);
	return $body;
}

?>
