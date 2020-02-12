rcube_webmail.prototype.imaplabels_setselect = function(list) {
	let id = list.get_single_selection();
	if (id === null) return;

	this.imaplabels_loadframe(id);
}

rcube_webmail.prototype.imaplabels_loadframe = function(name) {
	if (this.env.contentframe && window.frames && window.frames[this.env.contentframe]) {
		let target = window.frames[this.env.contentframe];
		target.location.href = this.env.comm_path + encodeURI('&_action=plugin.imap_labels-load&_label=' + name);
	}
}

function flagMail(uid, row) {
	if (typeof rcmail.env === 'undefined' || typeof rcmail.env.messages === 'undefined') return;

	let message = rcmail.env.messages[uid];
	var rowObj = $(row.obj);

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
}

function handleMailTask() {
	rcmail.addEventListener('init', function(e) {
		rcmail.register_command('plugin.imap_labels-set', function() {
			if (typeof rcmail_ui === undefined)
				rcmail_ui = UI;

			if (typeof rcmail_ui.show_popupmenu === undefined)
				rcmail_ui.show_popup('imaplabels_button_popup');
			else
				rcmail_ui.show_popupmenu('imaplabels_button_popup');
			return false;
		}, true);
	});
	rcmail.addEventListener('insertrow', function(e) {
		flagMail(e.uid, e.row);
	});
}

function handlePreferencesTask() {
	rcmail.addEventListener('init', function(e) {
		if (rcmail.env.action.startsWith('plugin.imap_labels')) {
			rcmail.register_command('plugin.imap_labels-save', function (e) {
				if (!rcmail.gui_objects.labelform) return;
				rcmail.gui_objects.labelform.submit();
			});
			rcmail.register_command('plugin.imap_labels-labeladd', function (e) {
				rcmail.imaplabels_loadframe('');
			});

			if (rcmail.gui_objects.labelform) {
				rcmail.enable_command('plugin.imap_labels-save', true);
			}
			rcmail.enable_command('plugin.imap_labels-labeladd', true);

			if (rcmail.gui_objects.imaplabelslist) {
				rcmail.imaplabels_list = new rcube_list_widget(rcmail.gui_objects.imaplabelslist, { multiselect: false, draggable: false, keyboard: true });
				rcmail.imaplabels_list.init().focus();
				rcmail.imaplabels_list.addEventListener('select', function(e) {
					rcmail.imaplabels_setselect(e);
				});
			}
		}
	});
}

$(document).ready(function() {
	switch (rcmail.env.task) {
		case 'mail':
			handleMailTask();
			break;
		case 'settings':
			handlePreferencesTask();
			break;
	}
});