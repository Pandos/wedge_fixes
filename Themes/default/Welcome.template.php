<?php
/**
 * Wedge
 *
 * Displays the custom homepage. Hack away!
 *
 * @package wedge
 * @copyright 2010-2012 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_main()
{
	echo '
	<div class="windowbg2 wrc">
		<h1>
			Welcome, hacker!
		</h1>
		<ul>
			<li><a href="<URL>?action=boards">Board index</a></li>
			<li><a href="<URL>?action=pm">Personal messages</a></li>
		</ul>
	</div>';
}

function template_info_before()
{
	echo '
		<div class="roundframe" style="margin: 16px 0">';
}

// This one is just here to show you that layers can get _before_before,
// _before_override, _before_after and _after_* overrides ;)
// It only works on layers, though!
function template_info_center_before_after()
{
	echo '
		<div style="height: 8px"></div>';
}

function template_info_after()
{
	echo '
		</div>';
}

function template_thoughts_before()
{
	echo '
	<div class="roundframe" style="margin: 16px 0">';
}

function template_thoughts_after()
{
	echo '
	</div>';
}

function template_thoughts($limit = 18)
{
	global $txt, $user_info, $context;

	$is_thought_page = isset($_GET['s']) && $_GET['s'] === 'thoughts';

	if (!$is_thought_page)
		echo '
		<we:title>
			<div class="thought_icon"></div>
			', $txt['thoughts'], '... (<a href="<URL>?s=thoughts">', $txt['all_pages'], '</a>)
		</we:title>';

	echo '
		<div class="tborder" style="margin: 5px 0 10px 0">
		<table class="w100 cp0 cs0 thought_list">';

	if ($is_thought_page)
		echo '
			<tr><td colspan="2" class="titlebg" style="padding: 4px">', $txt['pages'], ': ', $context['page_index'], '</td></tr>';

	if (!$user_info['is_guest'])
		echo '
			<tr id="new_thought">
				<td class="bc">{date}</td><td class="windowbg thought">{uname} &raquo; {text}</td>
			</tr>';

	foreach ($context['thoughts'] as $id => $thought)
	{
		$col = empty($col) ? 2 : '';
		echo '
			<tr>
				<td class="bc', $col, '">', $thought['updated'], '</td>
				<td class="windowbg', $col, ' thought"><a id="t', $id, '"></a><a href="<URL>?action=profile;u=', $thought['id_member'], '">',
				$thought['owner_name'], '</a> &raquo; ', $thought['text'], '</td>
			</tr>';
	}

	if ($is_thought_page)
		echo '
			<tr><td colspan="2" class="titlebg" style="padding: 4px">', $txt['pages'], ': ', $context['page_index'], '</td></tr>';

	echo '
		</table>
		</div>';
}

?>