function rcm_imap_label_insert(uid, row) {
	if (typeof rcmail.env == 'undefined' ||
		typeof rcmail.env.messages == 'undefined')
		return;

	var message = rcmail.env.messages[uid];
	var rowObj = $(row.obj);

	if (message.flags && message.flags.imap_labels) {
		if (message.flags.imap_labels.length) {
			for (var l in message.flags.imap_labels) {
				var label = message.flags.imap_labels[l];
				var color = rcmail.env.imap_label_colors[label]['color'];

				if (rowObj.find("td.threads")) {
					rowObj.find("td.threads").css("background-color", color);
				}
				rowObj.find("td.subject").css("background-color", color);
				rowObj.find("td.flags").css("background-color", color);
			}
		}
	}
}

$(document).ready(function() {
	rcmail.addEventListener('insertrow', function(event) {
		rcm_imap_label_insert(event.uid, event.row);
	});
});