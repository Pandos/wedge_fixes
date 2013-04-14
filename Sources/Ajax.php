<?php
/**
 * Wedge
 *
 * This file provides the handling for some of the AJAX operations, namely the very generic ones fired through action=ajax.
 *
 * @package wedge
 * @copyright 2010-2013 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/**
 * This function handles the initial interaction from action=ajax, loading the template then directing process to the appropriate handler.
 *
 * @see GetJumpTo()
 * @see ListMessageIcons()
 */
function Ajax()
{
	loadTemplate('Xml');

	$sub_actions = array(
		'jumpto' => array(
			'function' => 'GetJumpTo',
		),
		'messageicons' => array(
			'function' => 'ListMessageIcons',
		),
		'thought' => array(
			'function' => 'Thought',
		),
	);
	if (!isset($_REQUEST['sa'], $sub_actions[$_REQUEST['sa']]))
		fatal_lang_error('no_access', false);

	$sub_actions[$_REQUEST['sa']]['function']();
}

/**
 * Produces the list of boards and categories for the jump-to dropdown.
 *
 * - Uses the {@link getBoardList()} function in Subs-MessageIndex.php.
 * - Only displays boards the user has permissions to see (does not honor ignored boards preferences)
 * - The current board (if there is a current board) is indicated, and so will be in the dataset returned via the template.
 * - Passes control to the jump_to block in the main Xml template.
 */
function GetJumpTo()
{
	global $context, $settings;

	// Find the boards/cateogories they can see.
	// Note: you can set $context['current_category'] if you have too many boards and it kills performance.
	loadSource('Subs-MessageIndex');
	$boardListOptions = array(
		'use_permissions' => true,
		'selected_board' => isset($context['current_board']) ? $context['current_board'] : 0,
		'current_category' => isset($context['current_category']) ? $context['current_category'] : null, // null to list all categories
	);
	$url = !empty($settings['pretty_enable_filters']) ? '<URL>?board=' : '';
	$jump_to = getBoardList($boardListOptions);
	$skip_this = isset($_REQUEST['board']) ? $_REQUEST['board'] : 0;
	$json = array();

	foreach ($jump_to as $id_cat => $cat)
	{
		$json[] = array(
			'name' => un_htmlspecialchars(strip_tags($cat['name'])),
		);
		foreach ($cat['boards'] as $board)
			$json[] = array(
				'level' => (int) $board['child_level'],
				'id' => $board['id'] == $skip_this ? 'skip' : ($url ? $url . $board['id'] . '.0' : $board['id']),
				'name' => un_htmlspecialchars(strip_tags($board['name'])),
			);
	}

	// This will be returned as JSON, saving bytes and processing time.
	return_json($json);
}

/**
 * Produces a list of the message icons, used for the AJAX change-icon selector within the topic view.
 *
 * - Uses the {@link getMessageIcons()} function in Subs-Editor.php to achieve this.
 * - Uses the current board (from $board) to ensure that the correct iconset is loaded, as icons can be per-board.
 * - Passes control to the message_icons block in the main Xml template.
 */
function ListMessageIcons()
{
	global $board;

	loadSource('Subs-Editor');
	$icons = getMessageIcons($board);

	$str = '';
	foreach ($icons as $icon)
		$str .= '
	<icon value="' . $icon['value'] . '" url="' . $icon['url'] . '"><![CDATA[' . cleanXml('<img src="' . $icon['url'] . '" alt="' . $icon['value'] . '" title="' . $icon['name'] . '">') . ']]></icon>';

	return_xml('<we>', $str, '</we>');
}

function Thought()
{
	global $context;

	if (isset($_REQUEST['personal']))
		ThoughtPersonal();

	// !! We need we::$user if we're going to allow the editing of older messages... Don't forget to check for sessions?
	if (we::$is_guest)
		exit;

	// !! Should we use censorText at store time, or display time...? we::$user (Load.php:1696) begs to differ.
	$text = isset($_POST['text']) ? westr::htmlspecialchars(trim($_POST['text']), ENT_QUOTES) : '';
	if (empty($text) && empty($_GET['in']) && !isset($_REQUEST['remove']))
		exit;

	if (!empty($text))
	{
		loadSource('Class-Editor');
		wedit::preparsecode($text);
	}
	wetem::load('thought');

	// Original thought ID (in case of an edit.)
	$oid = isset($_POST['oid']) ? (int) $_POST['oid'] : 0;
	$pid = !empty($_POST['parent']) ? (int) $_POST['parent'] : 0;
	$mid = !empty($_POST['master']) ? (int) $_POST['master'] : 0;

	// If we have a parent, then get the member data for the parent thought.
	if ($pid)
	{
		$request = wesql::query('
			SELECT m.id_member, m.real_name
			FROM {db_prefix}thoughts AS t
			LEFT JOIN {db_prefix}members AS m ON t.id_member = m.id_member
			WHERE id_thought = {int:id_parent}
			LIMIT 1',
			array(
				'id_parent' => $pid,
			)
		);
		list ($parent_id, $parent_name) = wesql::fetch_row($request);
		wesql::free_result($request);
	}

	// Is this a public thought?
	$privacy = isset($_POST['privacy']) && preg_match('~-?[\d,]+~', $_POST['privacy']) ? $_POST['privacy'] : '-3';

	/*
		// Delete thoughts when they're older than 3 years...?
		// Commented out because it's only useful if your forum is very busy...

		wesql::query('
			DELETE FROM {db_prefix}thoughts
			WHERE updated < UNIX_TIMESTAMP() - 3 * 365 * 24 * 3600
		');
	*/

	// Are we asking for an existing thought?
	if (!empty($_GET['in']))
	{
		$request = wesql::query('
			SELECT thought
			FROM {db_prefix}thoughts
			WHERE id_thought = {int:original_id}' . (allowedTo('moderate_forum') ? '' : '
			AND id_member = {int:id_member}
			LIMIT 1'),
			array(
				'id_member' => we::$id,
				'original_id' => $_GET['in'],
			)
		);
		list ($thought) = wesql::fetch_row($request);
		wesql::free_result($request);

		// Cheating a little... Just return plain text. This bypasses a bug in jQuery 1.9.
		echo un_htmlspecialchars($thought);
		exit;
	}

	// Is it an edit?
	if (!empty($oid))
	{
		$request = wesql::query('
			SELECT t.id_thought, t.thought, t.id_member, m.real_name
			FROM {db_prefix}thoughts AS t
			INNER JOIN {db_prefix}members AS m ON m.id_member = t.id_member
			WHERE t.id_thought = {int:original_id}' . (allowedTo('moderate_forum') ? '' : '
			AND t.id_member = {int:id_member}'),
			array(
				'id_member' => we::$id,
				'original_id' => $oid,
			)
		);
		list ($last_thought, $last_text, $last_member, $last_name) = wesql::fetch_row($request);
		wesql::free_result($request);
	}

	// Overwrite previous thought if it's just an edit.
	if (!empty($last_thought))
	{
		similar_text($last_text, $text, $percent);

		// Think before you think!
		if (isset($_REQUEST['remove']))
		{
			// Does the author actually use this thought?
			$old_thought = 's:10:"id_thought";s:' . strlen($last_thought) . ':"' . $last_thought . '"';
			$request = wesql::query('
				SELECT id_member, data
				FROM {db_prefix}members
				WHERE data LIKE {string:data}',
				array(
					'data' => $old_thought,
				)
			);
			list ($member, $data) = wesql::fetch_row($request);
			wesql::free_result($request);

			// Okay, time to delete it...
			wesql::query('
				DELETE FROM {db_prefix}thoughts
				WHERE id_thought = {int:id_thought}', array(
					'id_thought' => $last_thought,
				)
			);

			// If anyone was using it, then update to their last valid thought.
			if (!empty($member))
			{
				$request = wesql::query('
					SELECT id_thought, thought, privacy
					FROM {db_prefix}thoughts
					WHERE id_member = {int:member}
					AND id_master = {int:not_a_reply}
					ORDER BY id_thought DESC
					LIMIT 1',
					array(
						'member' => $member,
						'not_a_reply' => 0,
					)
				);
				list ($id_thought, $thought, $privacy) = wesql::fetch_row($request);
				wesql::free_result($request);

				// Update their user data to use the new valid thought.
				if (!empty($id_thought))
				{
					// A complete hack, not ashamed of it :)
					if ($member !== we::$id)
					{
						$real_user = we::$user;
						$real_id = we::$id;
						we::$id = $member;
						we::$user['data'] = $data;
					}
					updateMyData(array(
						'id_thought' => $id_thought,
						'thought' => $thought,
						'thought_privacy' => $privacy,
					));
					if (!empty($real_user))
					{
						we::$user = $real_user;
						we::$id = $real_id;
					}
				}
			}

			call_hook('thought_delete', array(&$last_thought, &$last_text));
			exit;
		}
		// If it's similar to the earlier version, don't update the time.
		else
		{
			$update = $percent >= 90 ? 'updated' : time();
			wesql::query('
				UPDATE {db_prefix}thoughts
				SET updated = {raw:updated}, thought = {string:thought}, privacy = {int:privacy}
				WHERE id_thought = {int:id_thought}', array(
					'id_thought' => $last_thought,
					'privacy' => $privacy,
					'updated' => $update,
					'thought' => $text
				)
			);
			call_hook('thought_update', array(&$last_thought, &$privacy, &$update, &$text));
		}
	}
	else
	{
		// Okay, so this is a new thought... Insert it, we'll cache it if it's not a comment.
		wesql::query('
			INSERT IGNORE INTO {db_prefix}thoughts (id_parent, id_member, id_master, privacy, updated, thought)
			VALUES ({int:id_parent}, {int:id_member}, {int:id_master}, {string:privacy}, {int:updated}, {string:thought})', array(
				'id_parent' => $pid,
				'id_member' => we::$id,
				'id_master' => $mid,
				'privacy' => $privacy,
				'updated' => time(),
				'thought' => $text
			)
		);
		$last_thought = wesql::insert_id();

		$user_id = $pid ? (empty($last_member) ? we::$id : $last_member) : 0;
		$user_name = empty($last_name) ? we::$user['name'] : $last_name;

		call_hook('thought_add', array(&$privacy, &$text, &$pid, &$mid, &$last_thought, &$user_id, &$user_name));
	}

	// Only update the thought area if it's a public comment, and isn't a comment on another thought...
	if (!$pid && !empty($last_thought))
		updateMyData(array(
			'id_thought' => $last_thought,
			'thought' => $text,
			'thought_privacy' => $privacy,
		));

	$text = parse_bbc_inline($text);

	// Is this a reply to another thought...? Then we should try and style it as well.
	if (!empty($user_id))
	{
		// @worg!!
		$privacy_icon = array(
			-3 => 'everyone',
			0 => 'members',
			5 => 'justme',
			20 => 'friends',
		);

		$text = '<div>' . ($privacy != -3 ? '<div class="privacy_' . @$privacy_icon[$privacy] . '"></div>' : '') . '<a id="t' . $last_thought . '"></a>'
			. '<a href="<URL>?action=profile;u=' . $user_id . '">' . (empty($user_name) ? '' : $user_name) . '</a> &raquo;'
			. ' @<a href="<URL>?action=profile;u=' . (empty($parent_id) ? 0 : $parent_id) . '">' . (empty($parent_name) ? 0 : $parent_name) . '</a>&gt;'
			. ' <span class="thought" id="thought_update' . $last_thought . '" data-oid="' . $last_thought
			. '" data-prv="' . $privacy . '"><span>' . $text . '</span></span></div>';
	}

	$date = !empty($user_id) ? '<date><![CDATA[<a href="<URL>?action=thoughts;in=' . $mid . '#t' . $last_thought . '"><img src="' . $theme['images_url'] . '/icons/last_post.gif" class="middle"></a> ' . timeformat(time()) . ']]></date>' : '';

	return_xml('<we><text id="', $last_thought, '"><![CDATA[', cleanXml($text), ']]></text>', $date, '</we>');
}

function ThoughtPersonal()
{
	// !! Also check for sessions..?
	if (we::$is_guest || empty($_REQUEST['in']))
		exit;

	// Get the thought text, and ensure it's from the current member.
	$request = wesql::query('
		SELECT id_thought, thought
		FROM {db_prefix}thoughts
		WHERE id_member = {int:member}
		AND id_thought = {int:thought}
		LIMIT 1',
		array(
			'member' => we::$id,
			'thought' => $_REQUEST['in'],
		)
	);
	list ($personal_id_thought, $personal_thought) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Update their user data to use the new valid thought.
	if (!empty($personal_id_thought))
		updateMemberData(we::$id, array('personal_text' => parse_bbc_inline($personal_thought)));

	exit;
}
