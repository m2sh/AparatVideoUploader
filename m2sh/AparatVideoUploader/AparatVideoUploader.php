<?php
/**
 *  Aparat Video Uploader
 * 
 *  @author  m2sh (m2shm2sh@gmail.com)
 *  @package aparat.com video uploader
 *  @version 1.0
 *  @description : simple crawler & uploader for uploading video into aparat.com video sharing website
 */

namespace m2sh\AparatVideoUploader;

 
class AparatVideoUploader {

    /**
     * user auhenticate information
     * username 
     * @var string
     */
    protected $username;
    
     /**
      * password
      * @var string
      */
    protected $password;

    /**
     * web http client
     * @var object
     */
    protected $client;
    
    /**
     * web crawler
     * @var object
     */
    protected $crawler;
    
    /**
     * login url 
     * @var string
     */
    protected $loginUrl =  "http://www.aparat.com/profile/login/authenticate";
    
    /**
     * is user logged in?
     * @var bool
     */
    public $isUserLoggedIn =  false;

    /**
     * is crawler ready to upload?
     * @var bool
     */
    public $isReadyToUpload =  false;

    
    
    function __construct($username = null,$password = null) {
        
        if(!is_null($username))
            $this->username = $username;
        
        if(!is_null($password))
            $this->password = $password;

        $this->client = new \Goutte\Client();

        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36';
        $this->client->setHeader('User-Agent',$ua);
        $this->client->setHeader('user-agent',$ua);
    }
    
    /**
     * Set Authenticate Information
     * @param string username
     * @param string password 
     * 
     * @return $this for nested function use
     */
    public function setAuthenticationInfo($username,$password) {
        $this->username = $username;
        $this->password = $password;
        
        return $this;
    }
    
    /**
     * login user into aparat.com and $crawler object updated with logined user
     * 
     */
    public function login() {
       $crawler = $this->client->request('get',$this->loginUrl);
       $form = $crawler->selectButton('btnSubmit')->form();
       
       $crawler = $this->client->submit($form,[
           "data[username]" => $this->username,
           "data[password]" => $this->password 
       ]);
       
       $error = $crawler->filter('.ui-state-error');
       if(count($error) > 0) {
          // $errorMessages = [];
          // $error->each(function ($node) {
          //   $errorMessages[] =  $node->text()."\n";
          // });
          
          throw new AparatException("Invalid Username or Password", 1);
          return;
       } else {
            $this->crawler = $crawler;
            $this->isUserLoggedIn = true;
       }

       return $this;
    }
    
    public function getVideoCategories() {
        $options = $this->crawler->filter('select[name="data[category]"] > option');
        
        $cats = [];
        foreach ($options as $option) {
            $cats[$option->getAttribute('value')] = $option->nodeValue;
        }
        
        unset($cats[0]);
        
        return $cats;
            
    }

    public function getVideoCategoryId($category) {
        $found = array_search($category, $this->getVideoCategories());
        if($found !== FALSE)  {
          return $found;
        } else {
          return 1;
        }

    }
    
    /**
     * preparing for uploading video
     * 
     */
    public function prepareUpload() {
       // goto upload page
       $link = $this->crawler->filter('.userDashboard_upbtn')->children()->link();
       if($link) {
            $this->crawler = $this->client->click($link); 
            $this->isReadyToUpload = true;   
       } else {
           throw new AparatException("Sorry. the upload link/button not found, will fix ASAP.", 1);
       }

       return $this;
    }
    
    public function uploadFromFile($file,$videoInfo) {
      if(!file_exists($file)) {
        throw new AparatException("File does not exists in " . $file, 1);
      }

      if(is_array($videoInfo) && !isset($videoInfo['title'],$videoInfo['description'],$videoInfo['category'])) {
        throw new AparatException("Some missing key in video info, see docs for more information", 1);
      }

      $this->prepareUpload();

      if($this->isReadyToUpload == false) {
        throw new AparatException("Preparing upload is required before upload file", 1);
      }

      // find upload form & uploading file
      $uploadForm = $this->crawler->filter('#upload_part1')->selectButton('btnSubmit')->form();
       
      $uploadedFile = $this->client->submit($uploadForm,[
        'video' => $file
      ]);
      
      $html = $uploadedFile->html();
      // if(strpos($html, 'parent.window.location.href = parent.window.location.href') !== 0) {
      //   throw new AparatException("duplicate video uploading please try an other file for upload.", 1);
      // } else
   
      if(strpos($html, 'parent.finishUpload()') == 0 && strpos($html, 'parent.window.location.href = parent.window.location.href') !== 0) {
        throw new AparatException("Error on uploading file, please try again", 1);
      }
      
      // configure uploaded video
      $videoInfoForm = $this->crawler->filter('#upload_part2')->selectButton('btnSubmit')->form();

      $information = [
        'data[title]' => $videoInfo['title'],
        'data[descr]' => $videoInfo['description'],
        'data[tags]' => implode(" - ", $videoInfo['tags']),
        'data[category]' => is_scalar($videoInfo['category']) ? $videoInfo['category'] : $this->getVideoCategoryId($videoInfo['category']),
        'data[comment]' => isset($videoInfo['comment_permission']) ? $videoInfo['comment_permission'] : 'yes'
      ];

      $submitedVideoInfo = $this->client->submit($videoInfoForm,$information);

      $errors = $submitedVideoInfo->filter('.err-msg');
      if(count($errors) > 0) {
        throw new AparatException("Some errors occured on setting video info, please try again", 1);
      } else {
        return true;
      }
       
       
    }

    public function getVideoList() {
      $url = "http://www.aparat.com/video/video/listuser/view/list/dashboard/yes/username/" . $this->username;
      $crawler = $this->client->request('get',$url);

      $videos = $crawler->filter('div#video-list .row_box');

      $videoList = [];
      for ($i=0; $i < count($videos); $i++) { 
        $video = $videos->eq($i);
        $videoList[] = [
            'title' => $video->filter('div.row_title_second h2 a')->text(),
            'description' => $video->filter('div.row_body_inner')->text(),
            'link' => $video->filter('div.row_title_second h2 a')->attr('href'),
            'remove_link' => $video->filter('div.row_btm_link a')->last()->attr('href')
        ];
      }

      return $videoList;
    }

    public function removeVideo($removeLink) {
      $crawler = $this->client->request('get',$removeLink);

      $success = $crawler->filter('.ui-state-success p')->text();
      
      if(strpos($success, 'ویدیو با موفقیت حذف شد....') !== false) {
        return true;
      } else {
        return false;
      }
    }
}
