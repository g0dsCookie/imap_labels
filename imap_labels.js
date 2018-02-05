$(document).ready(function() {
	rcmail.addEventListener('insertrow', function(event) {
		if (typeof rcmail.env === 'undefined' || typeof rcmail.env.messages === 'undefined') return;

		var message = rcmail.env.messages[event.uid];
		var rowObj = $(event.row.obj);

		if (message.flags && message.flags.imap_labels && message.flags.imap_labels.length) {
			for (let l in message.flags.imap_labels) {
				let label = message.flags.imap_labels[l];
				let color = rcmail.env.imap_label_colors[label]['color'];
				let rgb = 'rgb(' + color['red'] + ',' + color['green'] + ',' + color['blue'] + ')';

				if (rowObj.find('td.threads')) {
					rowObj.find('td.threads').css('background-color', rgb);
				}
				rowObj.find('td.subject').css('background-color', rgb);
				rowObj.find('td.flags').css('background-color', rgb);
			}
		}
	});
});