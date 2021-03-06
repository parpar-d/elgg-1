<?php

/**
* Kaltura video client
* @package ElggKalturaVideo
* @license http://www.gnu.org/licenses/gpl.html GNU Public License version 3
* @author Ivan Vergés <ivan@microstudi.net>
* @copyright Ivan Vergés 2010
* @link http://microstudi.net/elgg/
**/

	// Load Elgg engine
		require_once(dirname(__FILE__)."/kaltura/api_client/includes.php");
		gatekeeper();

	// Get the current page's owner
		$page_owner = page_owner_entity();
		if ($page_owner === false || is_null($page_owner)) {
			$page_owner = $_SESSION['user'];
			set_page_owner($_SESSION['guid']);
		}

	// Get the post, if it exists
		$videopost = (int) get_input('videopost');
		$entryid = get_input('entryid');
		if (!($post = get_entity($videopost))) {
			$post = kaltura_get_entity($entryid);
		}
		if ($post) {

			if ($post->canEdit()) {

				$area1 = elgg_view_title(elgg_echo('kalturavideo:label:adminvideos').": ".elgg_echo('kalturavideo:label:editdetails'));
				$area1 .= elgg_view("kaltura/edit", array('entity' => $post));
				$body = elgg_view_layout("edit_layout", $area1);

			}

		}

	// Display page
		page_draw(sprintf(elgg_echo('kalturavideo:label:adminvideos').": ".elgg_echo('kalturavideo:label:editdetails'),$post->title),$body);

?>
