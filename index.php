<?php
//session_start();
include("Imgur.php");
require_once __DIR__ . '/src/Facebook/autoload.php';
//facebook canvas app setup
$fb = new Facebook\Facebook([
    'app_id' => '494529190717569',
    'app_secret' => 'e0b72b572e58d0e00058970b137dfaf8',
    'default_graph_version' => 'v2.8',
]);

$helper = $fb->getCanvasHelper();

$permissions = ['email']; // optionnal

try {
    if (isset($_SESSION['facebook_access_token'])) {
        $accessToken = $_SESSION['facebook_access_token'];
    } else {
        $accessToken = $helper->getAccessToken();
    }
} catch(Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}
if (isset($accessToken)) {
    if (isset($_SESSION['facebook_access_token'])) {
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    } else {
        $_SESSION['facebook_access_token'] = (string) $accessToken;

        // OAuth 2.0 client handler
        $oAuth2Client = $fb->getOAuth2Client();

        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);

        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }
    // validating the access token
    try {
        $request = $fb->get('/me');
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        if ($e->getCode() == 190) {
            unset($_SESSION['facebook_access_token']);
            $helper = $fb->getRedirectLoginHelper();
            $loginUrl = $helper->getLoginUrl('https://apps.facebook.com/isshahidul/', $permissions);
            echo "<script>window.top.location.href='".$loginUrl."'</script>";
            exit;
        }
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    // getting profile picture of the user
    try {
        $requestPicture = $fb->get('/me/picture?redirect=false&height=300'); //getting user picture
        $requestProfile = $fb->get('/me'); // getting basic info
        $picture = $requestPicture->getGraphUser();
        $profile = $requestProfile->getGraphUser();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
} else {
    $helper = $fb->getRedirectLoginHelper();
    $loginUrl = $helper->getLoginUrl('https://apps.facebook.com/isshahidul/');
    echo "<script>window.top.location.href='".$loginUrl."'</script>";
}
//image generate
$height=525;
$width=1000;
$box1_X=144;
$box1_y=144;
$box2_x=648;
$box2_y=144;
$rec_len=200;
//funny text
$title="Test Facebook App";
$usrName="1st User ";
$secondName="2nd User";
$resultText=$usrName." is a friend of ".$secondName;
//background image
$image=imagecreatefromjpeg("img/1.jpg");

$fbpic1=imagecreatefromjpeg($picture['url']);
$fbpic2=imagecreatefromjpeg($picture['url']);
$fbpic1New=imagescale($fbpic1,$rec_len,$rec_len);
$fbpic2New=imagescale($fbpic2,$rec_len,$rec_len);
imagecopy($image,$fbpic1New,$box1_X,$box1_y,0,0,imagesx($fbpic1New),imagesy($fbpic1New));
imagecopy($image,$fbpic2New,$box2_x,$box2_y,0,0,imagesx($fbpic2New),imagesy($fbpic2New));
$white = imagecolorallocate($image, 255, 255, 255);
$font = 'Sansation_Light.ttf';
imagettftext($image, 40, 0, 260, 100, $white, $font, $title);
imagettftext($image, 25, 0, 180, 440, $white, $font, $resultText);
ob_start ();
imagejpeg ($image);
$image_data = ob_get_contents ();
ob_end_clean ();
$base64 = base64_encode ($image_data);
//imgur
$api_key = "199a43028219fa4";
$api_secret = "80a58bf2e3f1a6045ac11167c463ed8692bccc8d";
$imgur = new Imgur($api_key, $api_secret);
$b=$imgur->upload()->string($base64);
$imageLink=$b['data']['link'];
//show image to canvas app
echo "<img src=".$imageLink." alt="."funapp".">";

imagedestroy($image);
imagedestroy($fbpic1);
imagedestroy($fbpic2);
imagedestroy($fbpic1New);
imagedestroy($fbpic2New);

