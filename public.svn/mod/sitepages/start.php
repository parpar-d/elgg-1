<?php
/**
 * Site Pages provides interfaces to create standard content-static pages
 * and to customize the front page layout and content.
 *
 * Formerly implemented as "external pages" and "custom index."
 *
 * @package SitePages
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Curverider Ltd
 * @copyright Curverider Ltd 2008-2010
 * @link http://elgg.org/
 *
 * @todo
 * 	Make sure this stuff doesn't show up in search.
 * 	DRY up actions and views
 * 	Use $entity->view to redirect to url of page.
 * 	The tool settings view is probably not needed as it can be added to the front page edit tab.
 * 	You can say pg/sitepages/edit|read/any_page_i_want and it will let you.
 */

/**
 * Start the site pages plugin.
 */
function sitepages_init() {
	require_once(dirname(__FILE__) . '/sitepages_functions.php');
	global $CONFIG;

	// register our subtype
	run_function_once('sitepages_runonce');

	// Register a page handler, so we can have nice URLs
	register_page_handler('sitepages', 'sitepages_page_handler');

	// Register a URL handler for external pages
	register_entity_url_handler('sitepages_url', 'object', 'sitepages');

	elgg_extend_view('footer/links', 'sitepages/footer_menu');
	elgg_extend_view('metatags', 'sitepages/metatags');

	// Replace the default index page if user has requested and the site is not running walled garden
	if (get_plugin_setting('ownfrontpage', 'sitepages') == 'yes') {
		register_plugin_hook('index', 'system', 'sitepages_custom_index');
	}

	// define our own ecml keywords and views
	register_plugin_hook('get_keywords', 'ecml', 'sitepages_ecml_keyword_hook');
	register_plugin_hook('get_views', 'ecml', 'sitepages_ecml_views_hook');
	
	// hook into the walled garden pages
	register_plugin_hook('public_pages', 'walled_garden', 'sitepages_public_pages');

	register_action("sitepages/add", FALSE, $CONFIG->pluginspath . "sitepages/actions/add.php");
	register_action("sitepages/addfront", FALSE, $CONFIG->pluginspath . "sitepages/actions/addfront.php");
	register_action("sitepages/addfrontsimple", FALSE, $CONFIG->pluginspath . "sitepages/actions/addfrontsimple.php");
	register_action("sitepages/addmeta", FALSE, $CONFIG->pluginspath . "sitepages/actions/addmeta.php");
	register_action("sitepages/edit", FALSE, $CONFIG->pluginspath . "sitepages/actions/edit.php");
	register_action("sitepages/delete", FALSE, $CONFIG->pluginspath . "sitepages/actions/delete.php");

	elgg_add_submenu_item(array(
		'text' => elgg_echo('sitepages'),
		'href' => "{$CONFIG->wwwroot}pg/sitepages/edit/front",
		'parent_id' => 'site',
	), 'admin', 'default');
}

/**
 * Registers the sitepages subtype to the right class.
 *
 * @return bool
 */
function sitepages_runonce() {
	return add_subtype('object', 'sitepages_page', 'ElggSitePage');
}

/**
 * Override the index if requested.
 *
 * @return TRUE on override
 */
function sitepages_custom_index() {
	// context is checked by the extended metatags view to print out its custom CSS
	//$context = get_context();
	//set_context('sitepages:front');

	//if ($contents = elgg_view('sitepages/custom_frontpage')) {
	//	page_draw(FALSE, $contents);

	//	set_context($context);
		// return TRUE to tell index.php we've got its content right here.
	//	return TRUE;
	//}

	//set_context($context);

	// return NULL to pass this to next in chain, or back to standard index.php.
	//return NULL;
	if (!include_once(dirname(dirname(__FILE__))) . "/sitepages/index.php") {
		return false;
	}
	return true;
}

/**
 *
 * @param unknown_type $expage
 * @return unknown_type
 *
 * //@todo is this needed?
 */
function sitepages_url($expage) {
	global $CONFIG;
	return $CONFIG->url . 'pg/sitepages/';
}

/**
 * Serve out views for site pages.
 *
 * @param unknown_type $page
 * @return unknown_type
 */
function sitepages_page_handler($page) {
	global $CONFIG;

	// for the owner block.
	if ($logged_in_guid = get_loggedin_userid()) {
		set_page_owner($logged_in_guid);
	}

	// sanity checking.
	// on bad params we'll forward so people will bookmark the correct URLs
	// @todo valid page names need to be pulled out into some sort of config var or admin option.
	$default_page = 'About';

	$action = isset($page[0]) ? $page[0] : FALSE;
	$page_type = isset($page[1]) ? $page[1] : FALSE;

	switch ($action) {
		case 'edit':
			admin_gatekeeper();
			$title = elgg_echo('sitepages');
			$content = sitepages_get_edit_section_content($page_type);

			break;

		case 'read':
			$title = elgg_echo('sitepages:' . strtolower($page_type));
			$content = sitepages_get_page_content($page_type);

			break;

		default:
			forward("{$CONFIG->site->url}pg/sitepages/read/$default_page");
			break;
	}

	page_draw($title, $content);
}


/**
 * Register some default keywords.
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $return_value
 * @param unknown_type $params
 * @return unknown_type
 */
function sitepages_ecml_keyword_hook($hook, $entity_type, $return_value, $params) {
	$return_value['loginbox'] = array(
		'view' => 'account/forms/login',
		'description' => elgg_echo('sitepages:ecml:keywords:loginbox:desc'),
		'usage' => elgg_echo('sitepages:ecml:keywords:loginbox:usage'),
		'restricted' => array('sitepages/custom_frontpage')
	);

	$return_value['userlist'] = array(
		'view' => 'sitepages/keywords/userlist',
		'description' => elgg_echo('sitepages:ecml:keywords:userlist:desc'),
		'usage' => elgg_echo('sitepages:ecml:keywords:userlist:usage'),
		'restricted' => array('sitepages/custom_frontpage')
	);

//	$return_value['sitestats'] = array(
//		'view' => 'sitepages/keywords/sitestats',
//		'description' => elgg_echo('sitepages:ecml:keywords:sitestats:desc'),
//		'usage' => elgg_echo('sitepages:ecml:keywords:sitestats:usage'),
//		'restricted' => array('sitepages/custom_frontpage')
//	);

	$return_value['entity'] = array(
		'description' => elgg_echo('sitepages:ecml:keywords:entity:desc'),
		'usage' => elgg_echo('sitepages:ecml:keywords:entity:usage'),
		'restricted' => array('sitepages/custom_frontpage')
	);

	$return_value['view'] = array(
		'description' => elgg_echo('sitepages:ecml:keywords:view:desc'),
		'usage' => elgg_echo('sitepages:ecml:keywords:view:usage'),
		'restricted' => array('sitepages/custom_frontpage')
	);

	return $return_value;
}

/**
 * Register the frontpage with ECML.
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $return_value
 * @param unknown_type $params
 */
function sitepages_ecml_views_hook($hook, $entity_type, $return_value, $params) {
	$return_value['sitepages/custom_frontpage'] = elgg_echo('sitepages:ecml:views:custom_frontpage');

	return $return_value;
}

function sitepages_public_pages($hook, $type, $return_value, $params) {
	global $CONFIG;
	
	$return_value[] = "{$CONFIG->url}pg/sitepages/read/About/";
	$return_value[] = "{$CONFIG->url}pg/sitepages/read/Terms/";
	$return_value[] = "{$CONFIG->url}pg/sitepages/read/Privacy/";
	
	return $return_value;
}

register_elgg_event_handler('init', 'system', 'sitepages_init');
