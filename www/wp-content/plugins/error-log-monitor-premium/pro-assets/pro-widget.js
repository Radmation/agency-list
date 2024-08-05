'use strict';

jQuery(function ($) {
	var widget = $('#ws_php_error_log');

	widget.find('.elm-summary-order').change(function () {
		$(this).prop('readonly', true).closest('form').submit();
	});

	var tabContainer = widget.find('.elm-tab-container'),
		defaultTab = tabContainer.data('elm-default-tab');

	tabContainer.tabs({
		active: defaultTab,
		activate: function (event, ui) {
			var tabSlug = $(ui.newTab).data('elm-tab-slug');
			if (tabSlug) {
				AjawV1.getAction('elm-save-active-widget-tab').post({tab: tabSlug});
			}
		}
	});

	/**
	 * Pad a string to a minimum length using the provided character.
	 *
	 * @param {String} inputString
	 * @param {Number} targetLength
	 * @param {String} padCharacter
	 * @return {String}
	 */
	function padEnd(inputString, targetLength, padCharacter) {
		targetLength = targetLength >> 0; //Floor if number or convert non-number to 0.
		padCharacter = String((typeof padCharacter !== 'undefined' ? padCharacter : ' '));
		if (inputString.length >= targetLength) {
			return inputString;
		} else {
			targetLength = targetLength - inputString.length;
			for (var i = 0; i < targetLength; i++) {
				inputString += padCharacter;
			}
			return inputString;
		}
	}

	/**
	 * @param {String} inputString
	 * @param {Number} count
	 * @return {String}
	 */
	function stringRepeat(inputString, count) {
		var result = '';
		for (var i = 0; i < count; i++) {
			result += inputString;
		}
		return result;
	}

	/**
	 * Get the details of a log entry as plain text.
	 *
	 * @param {jQuery} $row
	 * @return {string}
	 */
	function getEntryAsText($row) {
		var message = $row.find('.elm-log-message').text(),
			entryTimestamp = $row.find('.elm-timestamp').attr('title') || null;

		var plainText = message + '\n';

		var meta = [];

		if (entryTimestamp) {
			meta.push('Timestamp: ' + entryTimestamp);
		}

		//Add summary metadata.
		var $metadata = $row.find('.elm-entry-metadata');
		var firstSeen = $metadata.find('.elm-summary-item-age').attr('title');
		var lastSeen = $metadata.find('.elm-summary-timestamp').attr('title');
		var totalEvents = $metadata.find('.elm-summary-event-count').text();
		if (firstSeen) {
			meta.push(firstSeen);
		}
		if (lastSeen) {
			meta.push(lastSeen);
		}
		if (totalEvents) {
			meta.push('Total events: ' + totalEvents);
		}

		if (meta.length > 0) {
			plainText += '\n' + meta.join('\n') + '\n';
		}

		//Add the stack trace.
		var $traceItems = $row.find('.elm-stacktrace tr'),
			indexWidth = $traceItems.length.toString().length + 2, //Number + period + space.
			subItemLeadingSpace = stringRepeat(' ', indexWidth),
			stackTraceTexts = [];

		$traceItems.each(function () {
			var item = $(this);

			var $indexCell = item.find('.elm-stack-frame-index');
			if ($indexCell.length < 1) {
				stackTraceTexts.push(item.find('.elm-stack-frame-content').text());
				return;
			}

			var lines = [];
			var functionCall = item.find('.elm-function-call').text();
			if (functionCall !== '') {
				lines.push(functionCall);
			}

			var location = item.find('.elm-code-location').text();
			if (location !== '') {
				lines.push(location);
			}

			if (lines.length < 1) {
				return;
			}

			//Add the stack frame index to the first line.
			var index = padEnd($indexCell.text(), indexWidth, ' ');
			lines[0] = index + lines[0];
			//Indent the remaining lines so that they align with the text on the first line.
			for (var i = 1; i < lines.length; i++) {
				lines[i] = subItemLeadingSpace + lines[i];
			}

			stackTraceTexts.push(lines.join('\n'));
		});

		if (stackTraceTexts.length > 0) {
			var traceHeading = 'Stack Trace';
			plainText += '\n' + traceHeading + '\n' + stringRepeat('-', traceHeading.length) + '\n'
				+ stackTraceTexts.join('\n');
		}

		//Add context.
		var $parameterRows = $row.find('.elm-context-parameters tr');
		var parameterLines = [], longestParameterLength = 0;
		$parameterRows.each(function () {
			var $item = $(this),
				name = $item.find('th').text(),
				value = $item.find('td').text();

			//Skip the "show X more" link.
			if ($item.is('.elm-more-context-row')) {
				return;
			}

			if (name.length > longestParameterLength) {
				longestParameterLength = name.length;
			}

			parameterLines.push({name: name, value: value});
		});

		var formattedParameterLines = [];
		if (parameterLines.length > 0) {
			for (var i = 0; i < parameterLines.length; i++) {
				formattedParameterLines.push(
					padEnd(parameterLines[i].name, longestParameterLength, ' ')
					+ ' : ' + parameterLines[i].value
				);
			}

			var contextHeading = 'Context';
			plainText += '\n' + contextHeading + '\n' + stringRepeat('-', contextHeading.length) + '\n'
				+ formattedParameterLines.join('\n');
		}

		return plainText;
	}

	//Hide the "Copy" link if the current browser doesn't support clipboard operations.
	// noinspection JSUnresolvedFunction
	if ((typeof ClipboardJS === 'undefined') || !ClipboardJS.isSupported()) {
		widget.find('.elm-export-entry').hide();
	}

	//Handle the "Copy" link.
	var clipboard = new ClipboardJS('#ws_php_error_log .elm-export-entry', {
		container: document.getElementById('ws_php_error_log'),
		text: function (element) {
			return getEntryAsText($(element).closest('.elm-entry'));
		}
	});

	//Show a confirmation message after copying an entry to the clipboard.
	var confirmationFadeTimeout = null;
	clipboard.on('success', function (e) {
		if (confirmationFadeTimeout) {
			clearTimeout(confirmationFadeTimeout);
		}

		var notification = $('#elm-export-entry-confirmation');

		notification.show().finish();
		notification.position({
			my: 'center top+10',
			at: 'center bottom',
			of: e.trigger,
			collision: 'fit flip',
			within: $(e.trigger).closest('.elm-entry')
		});

		confirmationFadeTimeout = setTimeout(function () {
			confirmationFadeTimeout = null;
			notification.fadeOut(600);
		}, 2500);
	});

	clipboard.on('error', function (e) {
		if (console && console.error) {
			console.error(e);
		}
		alert('Error: ' + e);
	});

	//Prevent navigation to the URL of the "Copy" link because Clipboard.js doesn't do that automatically.
	widget.on('click', '.elm-export-entry', function (event) {
		event.preventDefault();
	});

	//Handle the "Delete Summary Data" button.
	widget.find('#elm-delete-summary-data').on('click', function () {
		var button = $(this);
		var confirmationMessage = button.data('confirmationText');

		if (!confirm(confirmationMessage)) {
			return false;
		}

		var oldButtonText = button.text();
		button.prop('disabled', true);
		button.text(button.data('progressText'));

		AjawV1.getAction('elm-delete-summary-data').post(
			{},
			function (response) {
				if (typeof response['message'] !== 'string') {
					alert('Error: Received an invalid response from the server.');
					return;
				}

				var panel = button.closest('#elm-summary-size-panel');
				var message = $('<p>').text(response.message).addClass('elm-summary-deletion-result');
				message.insertAfter(panel);
				panel.hide();
			},
			function (error) {
				//This request should never fail if the user has the required permissions,
				//but we'll handle the error anyway in case there's some unexpected bug.
				alert('Error: ' + JSON.stringify(error));
				button.text(oldButtonText).prop('disabled', false);
			}
		);

		return false;
	});
});