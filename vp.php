<?php


date_default_timezone_set('UTC');
 
require_once('config.php');
	
function generateS3QueryStr($content_item, $bucket, $platform)
{
	
	$accessKey = AWS_ACCESSKEY; 
	$secretKey = AWS_SECRETKEY;
	$platform_name = ($platform)? $platform.'/':'';

	$timestamp = strtotime("+10 minutes");
	$strtosign = "GET\n\n\n".$timestamp."\n/".$bucket."/".$platform_name.$content_item;

	$signature = urlencode(base64_encode(hash_hmac("sha1", utf8_encode($strtosign), $secretKey, true)));

	return "AWSAccessKeyId=".$accessKey."&Expires=".$timestamp."&Signature=".$signature;

}

function get_folder_numbers($id)
{
	$thousands = floor($id/1000);
	$hundreds = $id - $thousands*1000;

	return $thousands . '/' . $hundreds . '/';
}

function get_contest_public_path($cid)
{
	$numbers = get_folder_numbers($cid);
	return 'c/'.$numbers;
}

function get_video_public_path($cid)
{
  return 'v/'.$cid.'/';
}

$bucket = isset($_GET['b'])? $_GET['b'] : '';
$platform = isset($_GET['p'])? $_GET['p'] : '';

$contest_id = isset($_GET['cid'])? $_GET['cid'] : 0;
$video = isset($_GET['v'])? $_GET['v'] : '';
$audio = isset($_GET['a'])? $_GET['a'] : '';

$user_id = isset($_GET['uid'])? $_GET['uid'] : 0;

$content_url = 'https://s3-eu-west-1.amazonaws.com/';

//AUDIO

$full_url = $content_url;
$full_url .= $bucket .'/';
$full_url .= ($platform)? $platform.'/':'';

$audio_path = get_contest_public_path($contest_id).'ec/'.$audio;
$audio_query_str = generateS3QueryStr($audio_path, $bucket, $platform);

$audio_url = $full_url. $audio_path . "?" .$audio_query_str;

//VIDEO

$video_path = get_video_public_path($contest_id).$video;
$video_query_str = generateS3QueryStr($video_path, $bucket, $platform);

$video_url = $full_url. $video_path . "?" .$video_query_str;

$result_video = md5(time().$audio_url.$video_url.$user_id);

//create tmp folder
if (!is_dir("tmp")) mkdir($folder, 0700, true);

//make the MUXING
$cmd = "ffmpeg -i '$video_url' -i '$audio_url' -map 0:v -map 1:a -c copy 'tmp/$result_video.mp4' 2>&1";

exec($cmd, $output, $result);
var_dump($output);
echo "$result<br />";

//Print out the video

if(!is_file("tmp/$result_video.mp4")){
	echo "$cmd<br />";
	echo "The video creation was not successful";
	exit();
}

header('Content-Type: video/mp4');
echo file_get_contents("tmp/$result_video.mp4");

//remove junk

$rmv = "rm -f 'tmp/$result_video.mp4'";

exec($rmv, $output2, $result2);
//var_dump($output2);

?>