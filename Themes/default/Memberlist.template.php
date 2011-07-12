<?php
/**
 * Wedge
 *
 * Displays the user-visible member list and limited search function form.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

// Displays a sortable listing of all members registered on the forum.
function template_main()
{
	global $context, $settings, $options, $scripturl, $txt;

	// Build the memberlist button array.
	$memberlist_buttons = array(
		'view_all_members' => array('text' => 'view_all_members', 'image' => 'mlist.gif', 'lang' => true, 'url' => $scripturl . '?action=mlist;sa=all', 'class' => 'active'),
		'mlist_search' => array('text' => 'mlist_search', 'image' => 'mlist.gif', 'lang' => true, 'url' => $scripturl . '?action=mlist;sa=search'),
	);

	echo '
	<div class="main_section" id="memberlist">
		<we:cat>', !isset($context['old_search']) ? '
			<span class="floatright">' . $context['letter_links'] . '</span>' : '', '
			', $txt['members_list'];

	echo '
		</we:cat>
		<div class="pagesection">', template_button_strip($memberlist_buttons, 'right'), '
			<div class="pagelinks floatleft">', $txt['pages'], ': ', $context['page_index'], '</div>
		</div>

		<div id="mlist" class="topic_table">
			<table class="table_grid w100 cs0">
			<thead>
				<tr class="catbg">';

	// Display each of the column headers of the table.
	foreach ($context['columns'] as $column)
	{
		// We're not able (through the template) to sort the search results right now...
		if (isset($context['old_search']))
			echo '
					<th scope="col"', isset($column['class']) ? ' class="' . $column['class'] . '"' : '', isset($column['width']) ? ' style="width: ' . $column['width'] . 'px"' : '', isset($column['colspan']) ? ' colspan="' . $column['colspan'] . '"' : '', '>
						', $column['label'], '</th>';
		// This is a selected column, so underline it or some such.
		elseif ($column['selected'])
			echo '
					<th scope="col" class="nowrap', isset($column['class']) ? ' ' . $column['class'] : '', '" style="width: auto"' . (isset($column['colspan']) ? ' colspan="' . $column['colspan'] . '"' : '') . '>
						<a href="' . $column['href'] . '" rel="nofollow">' . $column['label'] . ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif"></a></th>';
		// This is just some column... show the link and be done with it.
		else
			echo '
					<th scope="col"', isset($column['class']) ? ' class="' . $column['class'] . '"' : '', isset($column['width']) ? ' style="width: ' . $column['width'] . 'px"' : '', isset($column['colspan']) ? ' colspan="' . $column['colspan'] . '"' : '', '>
						', $column['link'], '</th>';
	}
	echo '
				</tr>
			</thead>
			<tbody>';

	// Assuming there are members loop through each one displaying their data.
	if (!empty($context['members']))
	{
		foreach ($context['members'] as $member)
		{
			echo '
				<tr ', empty($member['sort_letter']) ? '' : ' id="letter' . $member['sort_letter'] . '"', '>
					<td class="windowbg2">
						', $context['can_send_pm'] ? '<a href="' . $member['online']['href'] . '" title="' . $member['online']['text'] . '">' : '', $settings['use_image_buttons'] ? '<img src="' . $member['online']['image_href'] . '" alt="' . $member['online']['text'] . '">' : $member['online']['label'], $context['can_send_pm'] ? '</a>' : '', '
					</td>
					<td class="windowbg left">', $member['link'], '</td>
					<td class="windowbg2">', $member['show_email'] == 'no' ? '' : '<a href="' . $scripturl . '?action=emailuser;sa=email;uid=' . $member['id'] . '" rel="nofollow"><img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . ' ' . $member['name'] . '"></a>', '</td>';

		if (!isset($context['disabled_fields']['website']))
			echo '
					<td class="windowbg">', $member['website']['url'] != '' ? '<a href="' . $member['website']['url'] . '" target="_blank" class="new_win"><img src="' . $settings['images_url'] . '/www.gif" alt="' . $member['website']['title'] . '" title="' . $member['website']['title'] . '"></a>' : '', '</td>';

		// Group and date.
		echo '
					<td class="windowbg left">', empty($member['group']) ? $member['post_group'] : $member['group'], '</td>
					<td class="windowbg left">', $member['registered_date'], '</td>';

		if (!isset($context['disabled_fields']['posts']))
		{
			echo '
					<td class="windowbg2 nowrap" style="width: 15px">', $member['posts'], '</td>
					<td class="windowbg statsbar" style="width: 120px">';

			if (!empty($member['post_percent']))
				echo '
						<div class="bar" style="width: ', $member['post_percent'] + 4, 'px;">
							<div style="width: ', $member['post_percent'], 'px;"></div>
						</div>';

			echo '
					</td>';
		}

		echo '
				</tr>';
		}
	}
	// No members?
	else
		echo '
				<tr>
					<td colspan="', $context['colspan'], '" class="windowbg">', $txt['search_no_results'], '</td>
				</tr>';

	// Show the page numbers again. (makes 'em easier to find!)
	echo '
			</tbody>
			</table>
		</div>';

	echo '
		<div class="pagesection">
			<div class="pagelinks floatleft">', $txt['pages'], ': ', $context['page_index'], '</div>';

	// If it is displaying the result of a search show a "search again" link to edit their criteria.
	if (isset($context['old_search']))
		echo '
			<div class="floatright">
				<a href="', $scripturl, '?action=mlist;sa=search;search=', $context['old_search_value'], '">', $txt['mlist_search_again'], '</a>
			</div>';

	echo '
		</div>
	</div>';
}

// A page allowing people to search the member list.
function template_search()
{
	global $context, $settings, $options, $scripturl, $txt;

	// Build the memberlist button array.
	$memberlist_buttons = array(
		'view_all_members' => array('text' => 'view_all_members', 'image' => 'mlist.gif', 'lang' => true, 'url' => $scripturl . '?action=mlist;sa=all'),
		'mlist_search' => array('text' => 'mlist_search', 'image' => 'mlist.gif', 'lang' => true, 'url' => $scripturl . '?action=mlist;sa=search', 'class' => 'active'),
	);

	// Start the submission form for the search!
	echo '
	<form action="', $scripturl, '?action=mlist;sa=search" method="post" accept-charset="UTF-8">
		<div id="memberlist">
			<we:cat>', !empty($settings['use_buttons']) ? '
				<img src="' . $settings['images_url'] . '/buttons/search.gif">' : '', '
				', $txt['mlist_search'], '
			</we:cat>
			<div class="pagesection">
				', template_button_strip($memberlist_buttons, 'right'), '
			</div>';

	// Display the input boxes for the form.
	echo '
			<div id="memberlist_search" class="clear">
				<div class="roundframe">
					<div id="mlist_search" class="flow_hidden">
						<div id="search_term_input"><br>
							<strong>', $txt['search_for'], ':</strong>
							<input type="search" name="search" value="', $context['old_search'], '" size="28" class="search"> <input type="submit" name="submit" value="' . $txt['search'] . '">
						</div>
						<span class="floatleft">';

	$count = 0;
	foreach ($context['search_fields'] as $id => $title)
	{
		echo '
							<label><input type="checkbox" name="fields[]" value="', $id, '"', in_array($id, $context['search_defaults']) ? ' checked' : '', '>', $title, '</label><br>';
		// Halfway through?
		if (round(count($context['search_fields']) / 2) == ++$count)
			echo '
						</span>
						<span class="floatleft">';
	}

	echo '
						</span>
					</div>
				</div>
			</div>
		</div>
	</form>';
}

?>