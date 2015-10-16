<?php

use PHPUnit_Framework_TestCase as TestCase;


class AparatVideoUploaderTest extends TestCase {

	private $uploader;

	public function setUp() {
		$this->uploader = new \m2sh\AparatVideoUploader\AparatVideoUploader();

		$username = getenv('APARAT_USERNAME');
		$password =  getenv('APARAT_PASSWORD');

		$this->uploader->setAuthenticationInfo($username,$password);
	}

	public function testUserLogin() {
		$this->uploader->login();

		$this->assertTrue($this->uploader->isUserLoggedIn);

		return $this->uploader;
	}

	/**
	 * @depends testUserLogin 
	 */
	public function testPrepareUpload($uploader) {
		$uploader->login()->prepareUpload();

		$this->assertTrue($uploader->isReadyToUpload);

		return $uploader;
	}

	/**
	 * @depends testPrepareUpload
	 */
	public function testGetVideoCategories($uploader) {
		$data = json_decode(file_get_contents(__DIR__ . '/data/cats.json'),true);

		$cats = $uploader->getVideoCategories();

		$this->assertEquals($data,$cats);
	}

	/**
	 * @depends testPrepareUpload
	 */
	public function testUploadFromFile($uploader) {
		$file =  __DIR__ . "/video/ghost.mp4";

		$videoDetail = array(
		    'title' => 'پسری که روح می‌شود',
		    'description' => 'شوخی با پدر',
		    'category' => 2,
		    'tags' => array (
		        'روح',
		        'شوخی'
		    ),
		    'comment_permission' => 'no'
		);

		$this->assertTrue($uploader->uploadFromFile($file,$videoDetail));

		return $uploader;
	}	

	/**
	 * @depends testUploadFromFile
	 */
	public function testGetVideoList($uploader) {
		$videoList = $uploader->getVideoList();

		$this->assertGreaterThanOrEqual(1,count($videoList));

		return $videoList;
	}
	/**
	 * @depends testUploadFromFile
	 * @depends testGetVideoList
	 */
	public function testRemoveVideo($uploader,$videoList) {
		$lastVideo = $videoList[0];

		$this->assertTrue($uploader->removeVideo($lastVideo['remove_link']));
	}

}