<?php

namespace Steem4WP;

use SteemPHP\SteemAccount;
use SteemPHP\SteemPost;
use SteemPHP\SteemChain;

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
		return "...";
	}


	/**
	 * Get the account profile information
	 *
	 * @param      string  $author   The account name
	 */
	public function getAccount($author)
	{
		return $this->steemAccount->getAccount($author);
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
	 * @param      string  $permlink   			The permlink of the comment
	 * @param      string  $title   				The title of the comment
	 * @param      string  $body   					The body of the comment
	 * @param      string  $jsonMetadata   	The json data of the comment
	 *
	 * @return     array   The response of the action
	 */
	public function createPost($author, $permlink, $title, $body, $jsonMetadata)
	{
		return $this->steemPost->comment($this->getWif($author), null, null, $author, $permlink, $title, $body, $jsonMetadata);
	}

	/**
	 * Reply to a post
	 *
	 * @param      string  $parentAuthor   	The author of the parent comment
	 * @param      string  $parentPermlink  The permlink of the parent comment
	 * @param      string  $author   				The author of the comment
	 * @param      string  $body   					The body of the comment
	 * @param      string  $jsonMetadata   	The json data of the comment
	 *
	 * @return     array   The response of the action
	 */
	public function replyToPost($parentAuthor, $parentPermlink, $author, $body, $jsonMetadata)
	{
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

?>
