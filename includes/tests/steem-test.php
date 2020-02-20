<?php

include (__DIR__).'/../../vendor/autoload.php';
include (__DIR__).'/../steem/steem.php';

use PHPUnit\Framework\TestCase;

class SteemTest extends TestCase
{

	protected function setUp(): void
	{
		$this->steem = new Steem('https://anyx.io');
	}

	public function testGetAccount()
	{
		$this->assertArrayHasKey('name', $this->steem->getAccount('robertyan'));
  }

	public function testGetAccounts()
	{
		$accounts = $this->steem->getAccounts(['koei', 'robertyan']);
		$this->assertArrayHasKey('name', $accounts[0]);
		$this->assertArrayHasKey('name', $accounts[1]);
	}

	public function testFollowsCount()
	{
    $count = $this->steem->getFollowsCount('robertyan');
    $this->assertEquals($count['account'], 'robertyan');
    $this->assertTrue($count['follower_count'] > 100);
    $this->assertTrue($count['following_count'] > 100);
	}

	public function testGetFollowers()
	{
    $followers = $this->steem->getFollowers('robertyan');
    $this->assertIsInt($followers);
	}

	public function testGetFollowing()
	{
    $following = $this->steem->getFollowing('robertyan');
    $this->assertIsInt($following);
	}

	public function testGetPostContent()
	{
		$this->assertEquals($this->steem->getPostContent('robertyan', 'awesome-steem-for-steem-developers')['url'], '/cn/@robertyan/awesome-steem-for-steem-developers');
  }

	public function testGetPostReplies()
	{
    $this->assertEquals($this->steem->getPostReplies('robertyan', 'awesome-steem-for-steem-developers')[0]['parent_author'], 'robertyan');
	}

	public function testGetPostsByAuthor()
	{
		$this->assertArrayHasKey('permlink', $this->steem->getPostsByAuthor('robertyan', 3)[0]);
	}

	public function testGetPostsByFeed()
	{
		$this->assertArrayHasKey('permlink', $this->steem->getPostsByFeed('robertyan', 3)[0]);
	}

	public function testGetPostsByCreated()
	{
		$this->assertArrayHasKey('permlink', $this->steem->getPostsByCreated('travel', 3)[0]);
	}

	public function testGetPostsByTrending()
	{
		$this->assertArrayHasKey('permlink', $this->steem->getPostsByTrending('travel', 3)[0]);
	}

	public function testGetPostsByHot()
	{
		$this->assertArrayHasKey('permlink', $this->steem->getPostsByHot('travel', 3)[0]);
	}

	public function testComment()
	{
		$this->assertIsInt($this->SteemPost->comment("...", "koei", "steempeak-cn", "koei", null, "", "Test with SteemPHP", "{}"));
	}

	public function testDeleteComment()
	{
		$this->assertIsInt($this->SteemPost->deleteComment("...", "koei",  "re-koei-steempeak-cn-20200204t091738252z"));
	}

	public function testVote()
	{
		$this->assertIsInt($this->SteemPost->vote("...", "koei", "koei", "re-koei-steempeak-cn-20200203t130104730z", 20));
	}

	public function testUnVote()
	{
		$this->assertIsInt($this->SteemPost->unvote("...", "koei", "koei", "re-koei-steempeak-cn-20200203t130104730z"));
	}

	public function testReblog()
	{
		$this->assertIsInt($this->SteemPost->reblog("...", "koei", "robertyan", "awesome-steem-for-steem-developers"));
	}

}

?>
