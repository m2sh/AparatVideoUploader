# AparatVideoUploader
[![Build Status](https://travis-ci.org/m2sh/AparatVideoUploader.svg?branch=master)](https://travis-ci.org/m2sh/AparatVideoUploader)  

Simple Video Uploader for [Aparat.com](http://aparat.com) based on [Goutte](https://github.com/FriendsOfPHP/Goutte) .

###Installation 
Using `Composer` :  
`composer require m2sh/aparatvideouploader`  
Or  
Clone this repo with `git clone` command  
Then install Dependencies with `composer install`
###Requirments

 - Because of using `GuzzleHttp` client in `Goutte` this package only
   work on PHP version  5.5 & above.
 - An Account from [Aparat.com](http://aparat.com).
 
###Usage
First include composer auto loader :

    include 'vendor/autoload.php'
Create instalnce from uploader class :

    $uploader = new \m2sh\AparatVideoUploader\AparatVideoUploader();
Specify  `username` & `password` of your aparat.com account :

    $uploader->setAuthenticationInfo('YOUR USERNAME', 'YOUR PASSWORD');

###Methods
####Login
Login User to aparat.com :  
`$uploader->login();`  
To find out that the login is successful, check `isUserLoggedIn` property :

    if($uploader->isUserLoggedIn) {
	    // do something
    }

#### PrepareUpload
Prepares uploader to uploading video :  
`$uploader->prepareUpload();`  
Also you can use Chain method like this :  
`$uploader->login()->prepareUpload();`  
####GetVideoCategories
Get Video Categories for UploadVideo

    $categories = $uploader->getVideoCategories();
    echo $categories[0];
####UploadFromFile
Upload Video From file to your account :

    $file =  __DIR__ . "/video/ghost.mp4";
    
    $videoDetail = [
    	'title' => 'پسری که روح می‌شود',
    	'description' => 'شوخی با پدر',
	        'category' => 2,
		    'tags' => ['روح','شوخی'],
	    'comment_permission' => 'no'
    ];
    
    $uploader->uploadFromFile($file,$videoDetail);
#### GetVideoList
Get uploaded video list
`$videoList = $uploader->getVideoList();'`
#### RemoveVideo
Removes Video with given `remove_link` url from `GetVideoList` method :  
`$uploader->removeVideo($videoList[0]['remove_link']);`

###Issues
If you have find any bug in this package please create new issue.
 
