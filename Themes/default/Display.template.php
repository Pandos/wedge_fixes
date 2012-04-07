<?php
/**
 * Wedge
 *
 * Displays a given topic, be it a blog post or forum topic.
 *
 * @package wedge
 * @copyright 2010-2012 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_display_posts()
{
	global $context, $theme, $options, $txt, $scripturl, $settings, $board_info;

	// OK, we're going to need this!
	add_js_file('scripts/topic.js');

	// Show the topic information - icon, subject, etc.
	echo '
		<div id="forumposts"', $board_info['type'] == 'board' ? '' : ' class="blog"', '>
			<form action="<URL>?action=quickmod2;topic=', $context['current_topic'], '.', $context['start'], '" method="post" accept-charset="UTF-8" name="quickModForm" id="quickModForm" style="margin: 0" onsubmit="return window.oQuickModify ? oQuickModify.modifySave() : false">';

	$ignoredMsgs = array();
	$removableMessageIDs = array();
	$is_mobile = !empty($context['skin_options']['mobile']);
	$alternate = false;

	// Get all the messages...
	while ($message = $context['get_message']())
	{
		$ignoring = false;
		$alternate = !$alternate;
		if ($message['can_remove'])
			$removableMessageIDs[] = $message['id'];

		// Are we ignoring this message?
		if (!empty($message['is_ignored']))
		{
			$ignoring = true;
			$ignoredMsgs[] = $message['id'];
		}

		// Show the message anchor and a "new" anchor if this message is new.
		if ($message['id'] != $context['first_message'])
			echo '
			<a id="msg', $message['id'], '"></a>', $message['first_new'] ? '<a id="new"></a>' : '';

		echo '
			<div class="postbg', $message['alternate'] == 0 ? '' : '2', $message['approved'] ? '' : ' approve', $message['id'] !== $context['first_message'] ? '' : ' first-post', empty($context['skin_options']['sidebar']) || $context['skin_options']['sidebar'] === 'right' ? '' : ' right-side', '">
				<div class="post_wrapper', $is_mobile ? ' mobile' : '', '">';

		// Show information about the poster of this message.
		if (empty($context['skin_options']['sidebar']) || $context['skin_options']['sidebar'] !== 'left')
			echo '
					<div class="poster">',
						template_userbox($message), '
					</div>';

		// Done with the information about the poster... on to the post itself.
		echo '
					<div class="postarea">';

		if (!$is_mobile)
		{
			echo '
						<div class="postheader">
							<div class="keyinfo">
								<div class="messageicon">
									<img src="', $message['icon_url'] . '"', $message['can_modify'] ? ' id="msg_icon_' . $message['id'] . '"' : '', '>
								</div>
								<h5 id="subject_', $message['id'], '">
									<a href="', $message['href'], '" rel="nofollow">', $message['subject'], '</a>', $message['new'] ? '
									<div class="note">' . $txt['new'] . '</div>' : '', '
								</h5>
								<span>&#171; ', !empty($message['counter']) ? sprintf($txt['reply_number'], $message['counter']) : '', ' ', $message['time'], ' &#187;</span>
								<div id="msg_', $message['id'], '_quick_mod"></div>
							</div>';

			// If this is the first post, (#0) just say when it was posted - otherwise give the reply #.
			if ($message['has_buttons'])
			{
				echo '
							<ul class="quickbuttons">';

				// Can they like? Is it liked?
				if ($context['can_like'])
					echo '
								<li><a href="<URL>?action=like;topic=', $context['current_topic'], ';msg=', $message['id'], ';', $context['session_query'], '" class="', empty($context['liked_posts'][$message['id']]['you']) ? 'like' : 'unlike', '_button" id="like_button_', $message['id'], '">', empty($context['liked_posts'][$message['id']]['you']) ? $txt['like'] : $txt['unlike'], '</a></li>';

				// Can they reply? Have they turned on quick reply?
				if ($context['can_quote'] && !empty($options['display_quick_reply']))
					echo '
								<li><a href="<URL>?action=post;quote=', $message['id'], ';topic=', $context['current_topic'], '.', $context['start'], ';last=', $context['topic_last_message'], '" class="quote_button" id="quote_button_', $message['id'], '" onclick="return window.oQuickReply && oQuickReply.quote(this);">', $txt['quote'], '</a></li>';

				// So... quick reply is off, but they *can* reply?
				elseif ($context['can_quote'])
					echo '
								<li><a href="<URL>?action=post;quote=', $message['id'], ';topic=', $context['current_topic'], '.', $context['start'], ';last=', $context['topic_last_message'], '" class="quote_button">', $txt['quote'], '</a></li>';

				// Can the user modify the contents of this post?
				if ($message['can_modify'])
					echo '
								<li><a href="<URL>?action=post;msg=', $message['id'], ';topic=', $context['current_topic'], '.', $context['start'], '" class="modify_button">', $txt['modify'], '</a></li>';

				if (!empty($context['action_menu'][$message['id']]))
					echo '
								<li><a id="mm', $message['id'], '" class="acme more_button">', $txt['more_actions'], '</a></li>';

				// Show a checkbox for quick moderation?
				if ($message['can_remove'])
					echo '
								<li class="inline_mod_check" id="inline_mod_check_', $message['id'], '"></li>';

				echo '
							</ul>';
			}
			echo '
						</div>';
		}
		else
		{
			// If we're in mobile mode, we'll move the Quote and Modify buttons to the Action menu.
			$menu = $context['action_menu'][$message['id']];

			// Insert them after the previous message's id.
			if ($message['can_modify'])
				array_splice($menu, 1, 0, 'mo');

			if ($context['can_quote'])
				array_splice($menu, 1, 0, 'qu');

			if (empty($context['liked_posts'][$message['id']]['you']))
				array_splice($menu, 1, 0, 'lk');
			else
				array_splice($menu, 1, 0, 'uk');

			$context['action_menu'][$message['id']] = $menu;
			$context['action_menu_items_show'] += array_flip($menu);
		}

		// Ignoring this user? Hide the post.
		if ($ignoring)
			echo '
						<div id="msg_', $message['id'], '_ignored_prompt">
							', $txt['ignoring_user'], '
							<a href="#" id="msg_', $message['id'], '_ignored_link" class="hide">', $txt['show_ignore_user_post'], '</a>
						</div>';

		if ($is_mobile)
			echo '
						<h5 id="subject_', $message['id'], '"></h5>';

		// Show the post itself, finally!
		echo '
						<div class="post">';

		if (!$message['approved'] && $message['member']['id'] != 0 && $message['member']['id'] == $context['user']['id'])
			echo '
							<div class="approve_post">
								', $txt['post_awaiting_approval'], '
							</div>';
		echo '
							<div class="inner" id="msg_', $message['id'], '"', '>', $message['body'], '</div>
						</div>';

		// Can the user modify the contents of this post?  Show the modify inline image.
		if ($message['can_modify'])
			echo '
						<div class="modifybutton" id="modify_button_', $message['id'], '" title="', $txt['modify_msg'], '" onclick="if (window.oQuickModify) oQuickModify.modifyMsg(this);" onmousedown="return false;">&nbsp;</div>';

		// Assuming there are attachments...
		if (!empty($message['attachment']))
		{
			echo '
						<div id="msg_', $message['id'], '_footer" class="attachments smalltext">
							<div style="overflow: ', $context['browser']['is_firefox'] ? 'visible' : 'auto', '">';

			foreach ($message['attachment'] as $attachment)
			{
				if ($attachment['is_image'])
				{
					if ($attachment['thumbnail']['has_thumb'])
						echo '
								<a href="', $attachment['href'], ';image" id="link_', $attachment['id'], '" onclick="', $attachment['thumbnail']['javascript'], '"><img src="', $attachment['thumbnail']['href'], '" id="thumb_', $attachment['id'], '"></a><br>';
					else
						echo '
								<img src="', $attachment['href'], ';image" width="' . $attachment['width'] . '" height="' . $attachment['height'] . '"><br>';
				}
				echo '
								<a href="', $attachment['href'], '"><img src="' . $theme['images_url'] . '/icons/clip.gif" class="middle">&nbsp;' . $attachment['name'] . '</a>
								(', $attachment['size'], ($attachment['is_image'] ? ', ' . $attachment['real_width'] . 'x' . $attachment['real_height'] . ' - ' . $txt['attach_viewed'] : ' - ' . $txt['attach_downloaded']) . ' ' . $attachment['downloads'] . ' ' . $txt['attach_times'] . '.)<br>';
			}

			echo '
							</div>
						</div>';
		}

		echo '
						<div class="moderatorbar">
							<div class="modified" id="modified_', $message['id'], '">';

		// Show "Last Edit on Date by Person" if this post was edited.
		if ($theme['show_modify'] && !empty($message['modified']['name']))
			echo '
								', $txt['last_edit'], ' ', $message['modified']['time'], $message['modified']['name'] !== $message['member']['name'] ? ' ' . $txt['by'] . ' ' . (!empty($message['modified']['member']) ? '<a href="<URL>?action=profile;u=' . $message['modified']['member'] . '">' . $message['modified']['name'] . '</a>' : $message['modified']['name']) : '';

		echo '
							</div>';

		// Can we issue a warning because of this post?  Remember, we can't give guests warnings.
		if ($context['can_issue_warning'] && !$message['is_message_author'] && !$message['member']['is_guest'])
			echo '
							<div class="report">
								<a href="<URL>?action=profile;u=', $message['member']['id'], ';area=issuewarning;msg=', $message['id'], '"><img src="', $theme['images_url'], '/warn.gif" alt="', $txt['issue_warning_post'], '" title="', $txt['issue_warning_post'], '"></a>
							</div>';

		echo '
						</div>';

		// Did anyone like this post?
		if (!empty($context['liked_posts'][$message['id']]))
			echo '
						<div class="post_like">
							', template_show_likes($message['id']), '
						</div>';

		// Are there any custom profile fields for above the signature?
		if (!empty($message['member']['custom_fields']))
		{
			foreach ($message['member']['custom_fields'] as $custom)
			{
				if ($custom['placement'] != 2 || empty($custom['value']))
					continue;
				if (empty($shown))
				{
					$shown = true;
					echo '
						<div class="custom_fields_above_signature">
							<ul class="reset nolist">';
				}
				echo '
								<li>', $custom['value'], '</li>';
			}
			if (!empty($shown))
				echo '
							</ul>
						</div>';
		}

		// Show the member's signature?
		if (!empty($message['member']['signature']) && !empty($options['show_signatures']) && $context['signature_enabled'])
			echo '
						<div class="signature" id="msg_', $message['id'], '_signature">', $message['member']['signature'], '</div>';

		echo '
					</div>';

		// Show information about the poster of this message.
		if (!empty($context['skin_options']['sidebar']) && $context['skin_options']['sidebar'] === 'left')
			echo '
					<div class="poster">',
						template_userbox($message), '
					</div>';

		echo '
				</div>
			</div>
			<hr class="post_separator">';
	}

	echo '
			</form>
		</div>';

	if ($context['can_remove_post'])
		add_js('
	new InTopicModeration({
		sClass: \'inline_mod_check\',' . ($context['can_remove_post'] ? '
		sRemoveLabel: \'' . $txt['quickmod_delete_selected'] . '\',
		sRemoveConfirm: \'' . $txt['quickmod_confirm'] . '\',' : '') . ($context['can_restore_msg'] ? '
		sRestoreLabel: \'' . $txt['quick_mod_restore'] . '\',
		sRestoreConfirm: \'' . $txt['quickmod_confirm'] . '\',' : '') . '
		sStrip: \'moderationbuttons\',
		sFormId: \'quickModForm\'
	});');

	add_js('
	if (can_ajax)
	{
		var oQuickModify = new QuickModify({
			bShowModify: ', $theme['show_modify'] ? 'true' : 'false', ',
			iTopicId: ' . $context['current_topic'] . ',
			sTemplateBodyEdit: ' . JavaScriptEscape('
				<div id="quick_edit_body_container" style="width: 90%">
					<div id="error_box" style="padding: 4px" class="error"></div>
					<textarea class="editor" id="qm_post" rows="12" style="' . ($context['browser']['is_ie8'] ? 'width: 635px; max-width: 100%; min-width: 100%' : 'width: 100%') . '; margin-bottom: 10px" tabindex="' . $context['tabindex']++ . '">%body%</textarea>
					<input type="hidden" name="' . $context['session_var'] . '" value="' . $context['session_id'] . '">
					<input type="hidden" id="qm_topic" value="' . $context['current_topic'] . '">
					<input type="hidden" id="qm_msg" value="%msg_id%">
					<div class="right">
						<input type="submit" name="post" value="' . $txt['save'] . '" tabindex="' . $context['tabindex']++ . '" accesskey="s" onclick="return oQuickModify.modifySave();" class="save">&nbsp;&nbsp;' . ($context['show_spellchecking'] ? '<input type="button" value="' . $txt['spell_check'] . '" tabindex="' . $context['tabindex']++ . '" onclick="spellCheck(\'quickModForm\', \'message\');" class="spell">&nbsp;&nbsp;' : '') . '<input type="submit" name="cancel" value="' . $txt['form_cancel'] . '" tabindex="' . $context['tabindex']++ . '" onclick="return oQuickModify.modifyCancel();" class="cancel">
					</div>
				</div>') . ',
			sTemplateSubjectEdit: ' . JavaScriptEscape('<input type="text" style="width: 90%" id="qm_subject" value="%subject%" size="80" maxlength="80" tabindex="' . $context['tabindex']++ . '">') . ',
			sTemplateBodyNormal: \'%body%\',
			sTemplateSubjectNormal: ' . JavaScriptEscape('<a href="' . $scripturl . '?topic=' . $context['current_topic'] . '.msg%msg_id%#msg%msg_id%" rel="nofollow">%subject%</a>') . ',
			sErrorBorderStyle: \'1px solid red\'
		});

		aIconLists.push(new IconList({
			sBackReference: "aIconLists[" + aIconLists.length + "]",
			sIconIdPrefix: "msg_icon_",
			bShowModify: ', $theme['show_modify'] ? 'true' : 'false', ',
			iBoardId: ' . $context['current_board'] . ',
			iTopicId: ' . $context['current_topic'] . ',
			sLabelIconList: "' . $txt['message_icon'] . '",
			sBoxBackground: "transparent",
			sBoxBackgroundHover: "#ffffff",
			iBoxBorderWidthHover: 1,
			sBoxBorderColorHover: "#adadad",
			sContainerBackground: "#ffffff",
			sContainerBorder: "1px solid #adadad",
			sItemBorder: "1px solid #ffffff",
			sItemBorderHover: "1px dotted gray",
			sItemBackground: "transparent",
			sItemBackgroundHover: "#e0e0f0"
		}));
	}');

	if (!empty($ignoredMsgs))
		foreach ($ignoredMsgs as $msgid)
			add_js('
	new weToggle({
		bCurrentlyCollapsed: true,
		aSwappableContainers: [
			\'msg_' . $msgid . '_extra_info\',
			\'msg_' . $msgid . '\',
			\'msg_' . $msgid . '_footer\',
			\'msg_' . $msgid . '_quick_mod\',
			\'modify_button_' . $msgid . '\',
			\'msg_' . $msgid . '_signature\'
		],
		aSwapLinks: [
			{
				sId: \'msg_' . $msgid . '_ignored_link\',
				msgExpanded: \'\',
				msgCollapsed: ' . JavaScriptEscape($txt['show_ignore_user_post']) . '
			}
		]
	});');

	if (!empty($context['user_menu']))
	{
		$context['footer_js'] .= '
	new MiniMenu({';

		foreach ($context['user_menu'] as $user => $linklist)
			$context['footer_js'] .= '
		' . $user. ': [ "' . implode('", "', $linklist) . '" ],';

		$context['footer_js'] = substr($context['footer_js'], 0, -1) . '
	}, false, {';
		foreach ($context['user_menu_items'] as $key => $pmi)
		{
			if (!isset($context['user_menu_items_show'][$key]))
				continue;
			$context['footer_js'] .= '
		' . $key . ': [ ';
			foreach ($pmi as $type => $item)
				if ($type === 'caption')
					$context['footer_js'] .= (isset($txt[$item]) ? JavaScriptEscape($txt[$item]) : '\'\'') . ', ' . (isset($txt[$item . '_desc']) ? JavaScriptEscape($txt[$item . '_desc']) : '\'\'') . ', ';
				else
					$context['footer_js'] .= $item . ', ';
			$context['footer_js'] = substr($context['footer_js'], 0, -2) . ' ],';
		}
		$context['footer_js'] = substr($context['footer_js'], 0, -1) . '
	});';
	}

	if (!empty($context['action_menu']))
	{
		$context['footer_js'] .= '
	new MiniMenu({';

		foreach ($context['action_menu'] as $post => $linklist)
			$context['footer_js'] .= '
		' . $post . ': [ "' . implode('", "', $linklist) . '" ],';

		$context['footer_js'] = substr($context['footer_js'], 0, -1) . '
	}, true, {';
		foreach ($context['action_menu_items'] as $key => $pmi)
		{
			if (!isset($context['action_menu_items_show'][$key]))
				continue;
			$context['footer_js'] .= '
		' . $key . ': [ ';
			foreach ($pmi as $type => $item)
				if ($type === 'caption')
					$context['footer_js'] .= (isset($txt[$item]) ? JavaScriptEscape($txt[$item]) : '\'\'') . ', ' . (isset($txt[$item . '_desc']) ? JavaScriptEscape($txt[$item . '_desc']) : '\'\'') . ', ';
				else
					$context['footer_js'] .= $item . ', ';
			$context['footer_js'] = substr($context['footer_js'], 0, -2) . ' ],';
		}
		$context['footer_js'] = substr($context['footer_js'], 0, -1) . '
	});';
	}
}

function template_userbox(&$message)
{
	global $context, $settings, $txt, $theme, $options;

	$is_mobile = !empty($context['skin_options']['mobile']);

	if ($is_mobile)
	{
		if (!empty($context['action_menu'][$message['id']]))
			echo '
						<div class="tinyuser">
							<span>', timeformat($message['timestamp']), '</span>
							<ul class="quickbuttons">
								<li><a id="mm', $message['id'], '" class="acme more_button">', $txt['more_actions'], '</a></li>
							</ul>
						</div>';

		// Show avatar for mobile skins
		if (!empty($theme['show_user_images']) && !empty($options['show_avatars']) && !empty($message['member']['avatar']['image']))
			echo '
						<div class="avatar">
							<a href="<URL>?action=profile;u=', $message['member']['id'], '">
								', $message['member']['avatar']['image'], '
							</a>
						</div>';
	}

	echo '
						<h4>';

	// Show online and offline buttons?
	if (!$message['member']['is_guest'])
		echo '
							', $context['can_send_pm'] ? '<a href="' . $message['member']['online']['href'] . '" title="' . $message['member']['online']['label'] . '">' : '', '<img src="', $message['member']['online']['image_href'], '" alt="', $message['member']['online']['text'], '">', $context['can_send_pm'] ? '</a>' : '';

	// Show a link to the member's profile.
	echo '
							<a href="', $message['member']['href'], '" id="um', $message['id'], '_', $message['member']['id'], '" class="umme">', $message['member']['name'], '</a>
						</h4>
						<ul class="reset" id="msg_', $message['id'], '_extra_info">';

	// Show the member's custom title, if they have one.
	if (!empty($message['member']['title']) && !$is_mobile)
		echo '
							<li class="mtitle">', $message['member']['title'], '</li>';

	// Show the member's primary group (like 'Administrator') if they have one.
	if (!empty($message['member']['group']))
		echo '
							<li class="membergroup">', $message['member']['group'], '</li>';

	// Don't show these things for guests or mobile skins.
	if (!$message['member']['is_guest'] && !$is_mobile)
	{
		// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
		if ((empty($theme['hide_post_group']) || $message['member']['group'] == '') && $message['member']['post_group'] != '')
			echo '
							<li class="postgroup">', $message['member']['post_group'], '</li>';

		echo '
							<li class="stars">', $message['member']['group_stars'], '</li>';

		// Show avatars, images, etc.?
		if (!empty($theme['show_user_images']) && !empty($options['show_avatars']) && !empty($message['member']['avatar']['image']))
			echo '
							<li class="avatar">
								<a href="<URL>?action=profile;u=', $message['member']['id'], '">
									', $message['member']['avatar']['image'], '
								</a>
							</li>';

		// Show how many posts they have made.
		if (!isset($context['disabled_fields']['posts']))
			echo '
							<li class="postcount">', $txt['member_postcount'], ': ', $message['member']['posts'], '</li>';

		// Show the member's gender icon?
		if (!empty($theme['show_gender']) && $message['member']['gender']['image'] != '' && !isset($context['disabled_fields']['gender']))
			echo '
							<li class="gender">', $txt['gender'], ': ', $message['member']['gender']['image'], '</li>';

		// Show their personal text?
		if (!empty($theme['show_blurb']) && $message['member']['blurb'] !== '')
			echo '
							<li class="blurb">', $message['member']['blurb'], '</li>';

		// Any custom fields to show as icons?
		if (!empty($message['member']['custom_fields']))
		{
			$shown = false;
			foreach ($message['member']['custom_fields'] as $custom)
			{
				if ($custom['placement'] != 1 || empty($custom['value']))
					continue;
				if (empty($shown))
				{
					$shown = true;
					echo '
							<li class="im_icons">
								<ul>';
				}
				echo '
									<li>', $custom['value'], '</li>';
			}
			if ($shown)
				echo '
								</ul>
							</li>';
		}

		// Show the profile, website, email address, and personal message buttons.
		if ($theme['show_profile_buttons'])
			template_profile_icons($message);

		// Any custom fields for standard placement?
		if (!empty($message['member']['custom_fields']))
			foreach ($message['member']['custom_fields'] as $custom)
				if (empty($custom['placement']) || empty($custom['value']))
					echo '
							<li class="custom">', $custom['title'], ': ', $custom['value'], '</li>';

		// Are we showing the warning status?
		if ($message['member']['can_see_warning'])
			echo '
							<li class="warning">', $context['can_issue_warning'] && $message['member']['warning_status'] != 'ban' ? '<a href="<URL>?action=profile;u=' . $message['member']['id'] . ';area=issuewarning">' : '', '<img src="', $theme['images_url'], '/warning_', $message['member']['warning_status'], '.gif" alt="', $txt['user_warn_' . $message['member']['warning_status']], '">', $context['can_issue_warning'] && $message['member']['warning_status'] != 'ban' ? '</a>' : '', '<span class="warn_', $message['member']['warning_status'], '">', $txt['warn_' . $message['member']['warning_status']], '</span></li>';
	}
	// Otherwise, show the guest's email.
	elseif (!$is_mobile && !empty($message['member']['email']) && in_array($message['member']['show_email'], array('yes_permission_override', 'no_through_forum')))
		echo '
							<li class="email"><a href="<URL>?action=emailuser;sa=email;msg=', $message['id'], '" rel="nofollow">', $theme['use_image_buttons'] ? '<img src="' . $theme['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '">' : $txt['email'], '</a></li>';

	echo '
						</ul>';
}

function template_profile_icons(&$message)
{
	global $context, $theme, $txt;

	echo '
							<li class="profile">
								<ul>';
	// Don't show the profile button if you're not allowed to view the profile.
	if ($message['member']['can_view_profile'])
		echo '
									<li><a href="', $message['member']['href'], '">', $theme['use_image_buttons'] ? '<img src="' . $theme['images_url'] . '/icons/profile_sm.gif" alt="' . $txt['view_profile'] . '" title="' . $txt['view_profile'] . '">' : $txt['view_profile'], '</a></li>';

	// Don't show an icon if they haven't specified a website.
	if ($message['member']['website']['url'] != '' && !isset($context['disabled_fields']['website']))
		echo '
									<li><a href="', $message['member']['website']['url'], '" title="' . $message['member']['website']['title'] . '" target="_blank" class="new_win">', $theme['use_image_buttons'] ? '<img src="' . $theme['images_url'] . '/www_sm.gif" alt="' . $message['member']['website']['title'] . '">' : $txt['website'], '</a></li>';

	// Don't show the email address if they want it hidden.
	if (in_array($message['member']['show_email'], array('yes_permission_override', 'no_through_forum')))
		echo '
									<li><a href="<URL>?action=emailuser;sa=email;msg=', $message['id'], '" rel="nofollow">', $theme['use_image_buttons'] ? '<img src="' . $theme['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '">' : $txt['email'], '</a></li>';

	// Since we know this person isn't a guest, you *can* message them.
	if ($context['can_send_pm'])
		echo '
									<li><a href="<URL>?action=pm;sa=send;u=', $message['member']['id'], '" title="', $message['member']['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline'], '">', $theme['use_image_buttons'] ? '<img src="' . $theme['images_url'] . '/im_' . ($message['member']['online']['is_online'] ? 'on' : 'off') . '.gif" alt="' . ($message['member']['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']) . '">' : ($message['member']['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']), '</a></li>';

	// Show the IP address if you're suitably privileged.
	if ($message['can_see_ip'] && !empty($message['member']['ip']))
	{
		// Because this seems just a touch convoluted if a single line.
		if (!$context['can_moderate_forum'])
			echo '
									<li><a href="<URL>?action=help;in=see_member_ip" onclick="return reqWin(this);" class="helpc"><img src="', $theme['images_url'], '/ip.gif" alt="', $txt['ip'], ': ', $message['member']['ip'], '" title="', $txt['ip'], ': ', $message['member']['ip'], '"></a></li>';
		else
			echo '
									<li><a href="<URL>?action=', !empty($message['member']['is_guest']) ? 'trackip' : 'profile;u=' . $message['member']['id'] . ';area=tracking;sa=ip', ';searchip=', $message['member']['ip'], '"><img src="', $theme['images_url'], '/ip.gif" alt="', $txt['ip'], ': ', $message['member']['ip'], '" title="', $txt['ip'], ': ', $message['member']['ip'], '"></a></li>';
	}

	// Maybe they want to report this post to the moderator(s)?
	if ($context['can_report_moderator'] && !$message['is_message_author'])
		echo '
									<li><a href="<URL>?topic=', $context['current_topic'], '.0;action=report;msg=', $message['id'], '"><img src="', $theme['images_url'], '/report.gif" alt="', $txt['report_to_mod'], '" title="', $txt['report_to_mod'], '"></a></li>';

	echo '
								</ul>
							</li>';
}

function template_show_likes($message_id)
{
	global $context, $txt, $user_profile;

	$string = '';
	// Simplest case, it's just you.
	if (!empty($context['liked_posts'][$message_id]['you']) && empty($context['liked_posts'][$message_id]['names']))
		$string = $txt['you_like_this'];
	// So we have some names to display?
	elseif (!empty($context['liked_posts'][$message_id]['names']))
	{
		$base_id = !empty($context['liked_posts'][$message_id]['you']) ? 'you_' : '';
		if (!empty($context['liked_posts'][$message_id]['others']))
			$string = number_context($base_id . 'n_like_this', $context['liked_posts'][$message_id]['others']);
		else
			$string = $txt[$base_id . count($context['liked_posts'][$message_id]['names']) . '_like_this'];

		// OK so at this point we have the string with the number of 'others' added, and also 'You' if appropriate. Now to add other names.
		foreach ($context['liked_posts'][$message_id]['names'] as $k => $v)
			$string = str_replace('{name' . ($k + 1) . '}', '<a href="<URL>?action=profile;u=' . $v . '">' . $user_profile[$v]['real_name'] . '</a>', $string);
	}

	return $string;
}

function template_topic_poll()
{
	global $theme, $options, $context, $txt, $settings;

	if (empty($context['is_poll']))
		return;

	// Build the poll moderation button array.
	$poll_buttons = array(
		'vote' => array('test' => 'allow_return_vote', 'text' => 'poll_return_vote', 'url' => '<URL>?topic=' . $context['current_topic'] . '.' . $context['start']),
		'results' => array('test' => 'show_view_results_button', 'text' => 'poll_results', 'url' => '<URL>?topic=' . $context['current_topic'] . '.' . $context['start'] . ';viewresults'),
		'change_vote' => array('test' => 'allow_change_vote', 'text' => 'poll_change_vote', 'url' => '<URL>?action=poll;sa=vote;topic=' . $context['current_topic'] . '.' . $context['start'] . ';poll=' . $context['poll']['id'] . ';' . $context['session_query']),
		'lock' => array('test' => 'allow_lock_poll', 'text' => (!$context['poll']['is_locked'] ? 'poll_lock' : 'poll_unlock'), 'url' => '<URL>?action=poll;sa=lockvoting;topic=' . $context['current_topic'] . '.' . $context['start'] . ';' . $context['session_query']),
		'edit' => array('test' => 'allow_edit_poll', 'text' => 'poll_edit', 'url' => '<URL>?action=poll;sa=editpoll;topic=' . $context['current_topic'] . '.' . $context['start']),
		'remove_poll' => array('test' => 'can_remove_poll', 'text' => 'poll_remove', 'custom' => 'onclick="return confirm(' . JavaScriptEscape($txt['poll_remove_warn']) . ');"', 'url' => '<URL>?action=poll;sa=removepoll;topic=' . $context['current_topic'] . '.' . $context['start'] . ';' . $context['session_query']),
	);

	$show_voters = ($context['poll']['show_results'] || !$context['allow_vote']) && $context['allow_poll_view'];
	echo '
		<div class="poll_moderation">', template_button_strip($poll_buttons), '
		</div>
		<we:block class="poll windowbg" header="', $txt['poll'], '" footer="', empty($context['poll']['expire_time']) ? '' :
			($context['poll']['is_expired'] ? $txt['poll_expired_on'] : $txt['poll_expires_on']) . ': ' . $context['poll']['expire_time'] . ($show_voters ? ' - ' : ''),
			$show_voters ? $txt['poll_total_voters'] . ': ' . $context['poll']['total_votes'] : '', '">
			<h4>
				<img src="', $theme['images_url'], '/topic/', $context['poll']['is_locked'] ? 'normal_poll_locked' : 'normal_poll', '.png" style="vertical-align: -4px">
				', $context['poll']['question'], '
			</h4>';

	// Are they not allowed to vote but allowed to view the options?
	if ($context['poll']['show_results'] || !$context['allow_vote'])
	{
		$bar_num = 1;
		echo '
			<dl>';

		// Show each option with its corresponding percentage bar.
		foreach ($context['poll']['options'] as $option)
		{
			echo '
				<dt', $option['voted_this'] ? ' class="voted"' : '', '>', $option['option'], '</dt>
				<dd class="bar', $bar_num++, $bar_num % 2 ? ' alt' : '', $option['voted_this'] ? ' voted' : '', '">';

			if ($context['allow_poll_view'])
				echo '
					', $option['bar_ndt'], '
					<span class="percentage">', $option['votes'], ' (', $option['percent'], '%)</span>';

			echo '
				</dd>';
		}

		echo '
			</dl>';
	}
	// They are allowed to vote! Go to it!
	else
	{
		echo '
			<form action="<URL>?action=poll;sa=vote;topic=', $context['current_topic'], '.', $context['start'], ';poll=', $context['poll']['id'], '" method="post" accept-charset="UTF-8">';

		// Show a warning if they are allowed more than one option.
		if ($context['poll']['allowed_warning'])
			echo '
				<p class="smallpadding">', $context['poll']['allowed_warning'], '</p>';

		echo '
				<ul class="reset">';

		// Show each option with its button - a radio likely.
		foreach ($context['poll']['options'] as $option)
			echo '
					<li><label>', $option['vote_button'], ' ', $option['option'], '</label></li>';

		echo '
				</ul>
				<div class="sendpoll">
					<input type="submit" value="', $txt['poll_vote'], '">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				</div>
			</form>';
	}

	echo '
		</we:block>';
}

function template_quick_reply()
{
	global $options, $txt, $context, $settings;

	if (!$context['can_reply'] || empty($options['display_quick_reply']))
	{
		echo '
		<br class="clear">';
		return;
	}

	echo '
		<div id="quickreply">
			<we:cat>
				<a href="#" onclick="return window.oQuickReply && oQuickReply.swap();" onmousedown="return false;"><div id="qr_expand"', $options['display_quick_reply'] == 2 ? ' class="fold"' : '', '></div></a>
				<a href="#" onclick="return window.oQuickReply && oQuickReply.swap();" onmousedown="return false;">', $txt['quick_reply'], '</a>
			</we:cat>
			<div id="qr_options" class="roundframe', $options['display_quick_reply'] == 2 ? '' : ' hide', '">
				<p class="smalltext left">', $txt['quick_reply_desc'], '</p>', $context['is_locked'] ? '
				<p class="alert smalltext">' . $txt['quick_reply_warning'] . '</p>' : '', !empty($context['oldTopicError']) ? '
				<p class="alert smalltext">' . sprintf($txt['error_old_topic'], $settings['oldTopicDays']) . '</p>' : '', $context['can_reply_approved'] ? '' : '
				<em>' . $txt['wait_for_approval'] . '</em>', !$context['can_reply_approved'] && $context['require_verification'] ? '
				<br>' : '', '
				<form action="<URL>?board=', $context['current_board'], ';action=post2" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);">
					<input type="hidden" name="topic" value="', $context['current_topic'], '">
					<input type="hidden" name="subject" value="', $context['response_prefix'], $context['subject'], '">
					<input type="hidden" name="icon" value="xx">
					<input type="hidden" name="from_qr" value="1">
					<input type="hidden" name="notify" value="', $context['is_marked_notify'] || !empty($options['auto_notify']) ? '1' : '0', '">
					<input type="hidden" name="not_approved" value="', !$context['can_reply_approved'], '">
					<input type="hidden" name="goback" value="', empty($options['return_to_post']) ? '0' : '1', '">
					<input type="hidden" name="last" value="', $context['topic_last_message'], '">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '">';

	// Guests just need more.
	if ($context['user']['is_guest'])
			echo '
					<strong>', $txt['name'], ':</strong> <input type="text" name="guestname" value="', $context['name'], '" size="25" tabindex="', $context['tabindex']++, '" required>
					<strong>', $txt['email'], ':</strong> <input type="email" name="email" value="', $context['email'], '" size="25" tabindex="', $context['tabindex']++, '" required><br>';

	// Is visual verification enabled?
	if ($context['require_verification'])
		echo '
					<strong>', $txt['verification'], ':</strong>', template_control_verification($context['visual_verification_id'], 'quick_reply'), '<br>';

	echo '
					<div class="qr_content">
						<div id="bbcBox_message" class="hide"></div>
						<div id="smileyBox_message" class="hide"></div>',
						$context['postbox']->outputEditor(), '
					</div>
					<div class="floatleft padding">
						<input type="button" name="switch_mode" id="switch_mode" value="', $txt['switch_mode'], '" class="hide" onclick="if (window.oQuickReply) oQuickReply.switchMode();">
					</div>
					<div class="right padding">',
						$context['postbox']->outputButtons(), '
					</div>
				</form>
			</div>
		</div>';

	add_js('
	var oQuickReply = new QuickReply({
		bDefaultCollapsed: ', !empty($options['display_quick_reply']) && $options['display_quick_reply'] == 2 ? 'false' : 'true', ',
		iTopicId: ' . $context['current_topic'] . ',
		iStart: ' . $context['start'] . ',
		sContainerId: "qr_options",
		sImageId: "qr_expand",
		sJumpAnchor: "quickreply",
		sBbcDiv: "', $context['postbox']->show_bbc ? 'bbcBox_message' : '', '",
		sSmileyDiv: "', !empty($context['postbox']->smileys['postform']) || !empty($context['postbox']->smileys['popup']) ? 'smileyBox_message' : '', '",
		sSwitchMode: "switch_mode",
		bUsingWysiwyg: ', $context['postbox']->rich_active ? 'true' : 'false', '
	});');

	if ($context['show_spellchecking'] && (empty($context['footer']) || strpos($context['footer'], '"spell_form"') === false))
	{
		$context['footer'] .= '
<form action="<URL>?action=spellcheck" method="post" accept-charset="UTF-8" name="spell_form" id="spell_form" target="spellWindow"><input type="hidden" name="spellstring" value=""></form>';
		add_js_file('scripts/spellcheck.js');
	}
}

function template_report_success()
{
	global $context, $txt;

	if ($context['report_sent'])
		echo '
		<div class="windowbg" id="profile_success">
			', $txt['report_sent'], '
		</div>';
}

function template_display_draft()
{
	global $context, $txt;

	if ($context['draft_saved'])
		echo '
	<div class="windowbg" id="profile_success">
		', str_replace('{draft_link}', '<URL>?action=profile;area=showdrafts', $txt['draft_saved']), '
	</div>';
}

function template_title_upper()
{
	global $context;

	// Show the anchor for the top and for the first message. If the first message is new, say so. Then the title and prev/next navigation.
	echo '
		<a id="msg', $context['first_message'], '"></a>', $context['first_new_message'] ? '<a id="new"></a>' : '', '
		<div class="posthead">', $context['prevnext_prev'], '
			<div id="top_subject">', $context['subject'], '</div>', $context['prevnext_next'], '
		</div>';
}

function template_postlist_before()
{
	global $context, $txt;

	echo '
		<div class="pagesection">',
			template_button_strip($context['nav_buttons']['normal']), '
			<nav>', $txt['pages'], ': ', $context['page_index'], $context['menu_separator'], ' &nbsp;&nbsp;<a href="#" onclick="return go_down();"><strong>', $txt['go_down'], '</strong></a></nav>
		</div>', $context['browser']['is_ie6'] ? '
		<div class="clear"></div>' : '';
}

function template_postlist_after()
{
	global $context, $txt;

	echo '
		<div class="pagesection">',
			template_button_strip($context['nav_buttons']['normal']), '
			<nav>', $txt['pages'], ': ', $context['page_index'], $context['menu_separator'], ' &nbsp;&nbsp;<a href="#" onclick="return go_up();"><strong>', $txt['go_up'], '</strong></a></nav>
		</div>';
}

function template_title_lower()
{
	global $context;

	// Show the prev/next navigation again, but don't show the container if they're empty.
	if (empty($context['no_prevnext']))
		echo '
		<div class="posthead">',
			$context['prevnext_prev'],
			$context['prevnext_next'], '
		</div>';
}

function template_mod_buttons()
{
	global $context;

	echo '
		<div id="moderationbuttons">', template_button_strip($context['nav_buttons']['mod'], 'left', array('id' => 'moderationbuttons_strip')), '
		</div>';
}

function template_display_whoviewing()
{
	global $context, $txt, $theme, $settings;

	echo '
	<section>
		<we:title>
			<img src="', $theme['images_url'], '/icons/online.gif" alt="', $txt['online_users'], '">', $txt['who_title'], '
		</we:title>
		<p>';

	// Show just numbers...?
	if ($settings['display_who_viewing'] == 1)
		echo count($context['view_members']), ' ', count($context['view_members']) == 1 ? $txt['who_member'] : $txt['members'];
	// Or show the actual people viewing the topic?
	else
		echo empty($context['view_members_list']) ? '0 ' . $txt['members'] : implode(', ', $context['view_members_list']) . ((empty($context['view_num_hidden']) || $context['can_moderate_forum']) ? '' : ' (+ ' . $context['view_num_hidden'] . ' ' . $txt['hidden'] . ')');

	// Now show how many guests are here too.
	echo $txt['who_and'], $context['view_num_guests'], ' ', $context['view_num_guests'] == 1 ? $txt['guest'] : $txt['guests'], $txt['who_viewing_topic'], '
		</p>
	</section>';
}

// Show statistical style information...
function template_display_statistics()
{
	global $context, $txt, $theme;

	if (!$theme['show_stats_index'])
		return;

	echo '
	<section>
		<we:title>
			<img src="', $theme['images_url'], '/icons/info.gif" alt="', $txt['topic_stats'], '">
			', $txt['topic_stats'], '
		</we:title>
		<p>
			', number_context('views', $context['num_views']), '
			<br>', number_context('replies', $context['num_replies']), '
		</p>
	</section>';
}

function template_display_staff()
{
	global $context, $txt;

	echo '
	<section>
		<we:title>
			', count($context['link_moderators']) == 1 ? $txt['moderator'] : $txt['moderators'], '
		</we:title>
		<p>
			', implode('<br>', $context['link_moderators']), '
		</p>
	</section>';
}

?>