<?php
/**
* Elgg profile icon
* 
* @package ElggProfile
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
* @author Curverider Ltd <info@elgg.com>
* @copyright Curverider Ltd 2008-2010
* @link http://elgg.com/
*/

require_once(dirname(dirname(dirname(__FILE__))) . "/engine/start.php");

// Get the owning user
$user = page_owner_entity();
$username = $user->username;

// Get the size
$size = strtolower(get_input('size'));
if (!in_array($size,array('large','medium','small','tiny','master','topbar')))
	$size = "medium";

// If user doesn't exist, return default icon
if (!$user) {
	$path = elgg_view("icon/user/default/$size");
	header("Location: $path");
	exit;
}

// Try and get the icon
$filehandler = new ElggFile();
$filehandler->owner_guid = $user->getGUID();
$filehandler->setFilename("profile/" . $username . $size . ".jpg");

$success = false;
if ($filehandler->open("read")) {
	if ($contents = $filehandler->read($filehandler->size())) {
		$success = true;
	} 
}

if (!$success) {
	global $CONFIG;
	$path = elgg_view('icon/user/default/'.$size);
	header("Location: {$path}");
	exit;
}

header("Content-type: image/jpeg");
header('Expires: ' . date('r',time() + 864000));
header("Pragma: public");
header("Cache-Control: public");
header("Content-Length: " . strlen($contents));

$splitString = str_split($contents, 1024);

foreach($splitString as $chunk) {
	echo $chunk;
}