<?php

class imap_labels extends rcube_plugin
{

	private $rc;

	function init()
	{
		$this->rc = rcube::get_instance();

		switch ($this->rc->task)
		{
			case 'mail':
				$this->add_hook('messages_list', array($this, 'messages_list'));
				$this->add_hook('render_page', array($this, 'render_button_popup'));

				$this->add_button(array(
					'command' => 'plugin.imap_labels-submenu',
					'id' => 'imaplabels_btn',
					'title' => 'imap_labels',
					'domain' => $this->ID,
					'type' => 'link',
					'content' => ' ',
					'class' => 'button',
				), 'toolbar');

				$this->init_ui();
				break;
			case 'settings':
				$this->register_action('plugin.imap_labels-settings', array($this, 'imap_labels_settings'));
				$this->register_action('plugin.imap_labels-load', array($this, 'imap_labels_loadlabel'));
				$this->register_action('plugin.imap_labels-save', array($this, 'imap_labels_save'));
				$this->add_hook('settings_actions', array($this, 'settings_actions'));
				$this->init_ui();
				break;
		}
	}

	function init_ui()
	{
		if ($this->ui_initialized) return;

		$this->add_texts('localization/');

		$this->include_script('imap_labels.js');

		// include css
		$skin_path = $this->local_skin_path();
		switch ($this->rc->task)
		{
			case 'settings':
				$this->include_stylesheet("$skin_path/imap_labels.css");
				break;
		}

		$this->ui_initialized = true;
	}

	public function settings_actions($args)
	{
		$args['actions'][] = array(
			'action' => 'plugin.imap_labels-settings',
			'label' => 'imap_labels',
			'title' => 'imap_labels',
			'class' => 'imap_labels',
			'domain' => 'imap_labels',
		);
		return $args;
	}

	public function imap_labels_settings()
	{
		$this->rc->output->add_handlers(array(
			'imaplabelslist' => array($this, 'imaplabels_list'),
			'labelframe' => array($this, 'label_frame'),
		));

		$this->rc->output->set_pagetitle($this->gettext('imap_labels'));
		$this->rc->output->send('imap_labels.imap_labels');
	}

	public function imap_labels_loadlabel()
	{
		$this->rc->output->add_handlers(array(
			'labelform' => array($this, 'label_form'),
		));

		$this->rc->output->set_pagetitle($this->gettext('labelform'));
		$this->rc->output->send('imap_labels.labeledit');
	}

	public function imap_labels_save()
	{
		$labelbefore = rcube_utils::get_input_value('_labelid', rcube_utils::INPUT_POST);
		$label = rcube_utils::get_input_value('_label', rcube_utils::INPUT_POST);
		$name = rcube_utils::get_input_value('_name', rcube_utils::INPUT_POST);
		$color = rcube_utils::get_input_value('_color', rcube_utils::INPUT_POST);
		$error = '';
		list($red, $green, $blue) = sscanf($color, '#%02x%02x%02x');

		if (strlen($label) <= 0)
			$error = $this->gettext('label_notempty');
		else if (strlen($name) <= 0)
			$error = $this->gettext('name_notempty');

		if (strlen($labelbefore) == 0)
		{
			$result = $this->label_insert($label, $name, $red, $green, $blue);
			if ($result)
			{
				$error = $this->gettext('dberror');
				rcmail::write_log('errors', $result);
			}
		}
		else
		{
			if ($labelbefore != $label)
			{
				$result = $this->label_rename($labelbefore, $label);
				if ($result)
				{
					$error = $this->gettext('dberror');
					rcmail::write_log('errors', $result);
				}
			}

			$result = $this->label_update($label, $name, $red, $green, $blue);
			if ($result)
			{
				$error = $this->gettext('dberror');
				rcmail::write_log('errors', $result);
			}
		}

		if (strlen($error) > 0) $this->rc->output->show_message($error, 'error');
		else $this->rc->output->show_message($this->gettext('saved'));

		$this->rc->output->add_handlers(array(
			'labelform' => array($this, 'label_form'),
		));
		$this->rc->output->set_pagetitle($this->gettext('labelform'));
		$this->rc->output->send('imap_labels.labeledit');
	}

	public function imaplabels_list($attrib)
	{
		if (!strlen($attrib['id']))
			$attrib['id'] = 'rcmimaplabelslist';

		$labels = $this->get_user_labels();
		if (is_string($labels))
		{
			rcube::write_log('errors', 'Could not fetch labels for '.$this->rc->user-ID.': '.$labels);
			$this->rc->output->show_message($this->gettext('dberror'), 'error');
			return '';
		}

		if (!empty($attrib['type']) && $attrib['type'] == 'list')
		{
			$a_show_cols = array('name');

			if ($labels)
			{
				foreach ($labels as $idx => $set)
				{
					$result[] = array(
						'name' => $set['name'],
						'id' => $idx,
					);
				}
			}

			$out = $this->rc->table_output($attrib, $result, $a_show_cols, 'id');
		}

		$this->rc->output->add_gui_object('imaplabelslist', $attrib['id']);
		$this->rc->output->include_script('list.js');

		return $out;
	}

	public function label_frame($attrib)
	{
		return $this->rc->output->frame($attrib, true);
	}

	public function label_form($attrib)
	{
		if (!$attrib['id'])
			$attrib['id'] = 'rcmlabelform';

		$labelname = rcube_utils::get_input_value('_label', rcube_utils::INPUT_GPC);
		if (strlen($labelname) == 0)
		{
			$labelobj = array(
				'label' => '',
				'name' => '',
				'color' => array(
					'red' => 0,
					'green' => 0,
					'blue' => 0,
				),
			);
		}
		else
		{
			$labelobj = $this->get_user_label($labelname);
			if (!$labelobj || is_string($labelobj))
			{
				rcube::write_log('errors', 'Could not fetch label object for '.$this->rc->user->ID.':'.$labelname);
				if (is_string($labelobj)) rcube::write_log('errors', $labelobj);
				return '<span style="color:#ff0000"><b>'.$this->gettext('dberror').'</b></span>';
			}
		}

		$hiddenfields = new html_hiddenfield(array('name' => '_task', 'value' => $this->rc->task));
		$hiddenfields->add(array('name' => '_action', 'value' => 'plugin.imap_labels-save'));
		$hiddenfields->add(array('name' => '_labelid', 'value' => $labelname));

		$out = '<form id="labelform" name="labelform" action="./" method="post">';
		$out .= $hiddenfields->show();

		$field_id = '_label';
		$input_label = new html_inputfield(array(
			'name' => $field_id,
			'id' => $field_id,
			'size' => 64,
			'value' => $labelobj['label'],
		));
		$input_label = $input_label->show();
		$out .= sprintf("<label for=\"%s\"><b>%s:</b></label>%s<br/><br/>", $field_id, rcube::Q($this->gettext('imap_label')), $input_label);

		$field_id = '_name';
		$input_name = new html_inputfield(array(
			'name' => $field_id,
			'id' => $field_id,
			'size' => 64,
			'value' => $labelobj['name'],
		));
		$input_name = $input_name->show();
		$out .= sprintf("<label for=\"%s\"><b>%s:</b></label>%s<br/><br/>", $field_id, rcube::Q($this->gettext('imap_name')), $input_name);

		$field_id = '_color';
		$input_color = new html_inputfield(array(
			'name' => $field_id,
			'id' => $field_id,
			'type' => 'color',
			'value' => sprintf(
				"#%02x%02x%02x",
				$labelobj['color']['red'],
				$labelobj['color']['green'],
				$labelobj['color']['blue']),
		));
		$input_color = $input_color->show();
		$out .= sprintf("<label for=\"%s\"><b>%s:</b></label>%s", $field_id, rcube::Q($this->gettext('imap_color')), $input_color);

		$out .= "</form>";

		$this->rc->output->add_gui_object('labelform', 'labelform');

		return $out;
	}

	public function messages_list($args)
	{
		if (!isset($args['messages']) or !is_array($args['messages']))
			return $args;

		$knownflags = $this->get_user_labels();
		if (is_string($knownflags))
		{
			rcube::write_log('errors', 'Could not load user labels: '.$knownflags);
			$this->rc->output->set_env('imap_label_colors', '{}');
			return $args;
		}

		$this->rc->output->set_env('imap_label_colors', $knownflags);
		if (!is_array($knownflags) or count($knownflags) == 0)
			return $args;

		foreach ($args['messages'] as $message)
		{
			$message->list_flags['extra_flags']['imap_labels'] = array();
			if (!is_array($message->flags))
				continue;

			foreach ($message->flags as $flagname => $flagvalue)
			{
				$flag = is_numeric("$flagvalue") ? $flagname : $flagvalue;
				$flag = strtolower($flag);
				if (array_key_exists($flag, $knownflags))
					$message->list_flags['extra_flags']['imap_labels'][] = $flag;
			}
		}
		return $args;
	}

	public function label_insert($label, $name, $red, $green, $blue)
	{
		$dbh = $this->get_dbh();
		return $dbh->is_error($dbh->query(preg_replace(
			array('/%u/', '/%l/', '/%n/', '/%r/', '/%g/', '/%b/'),
			array($this->rc->user->ID, $dbh->escape($label), $dbh->escape($name), $red, $green, $blue),
			"INSERT INTO imap_labels (userId, label, name, red, green, blue) VALUES (%u, '%l', '%n', %r, %g, %b)"
		)));
	}

	public function label_update($label, $name, $red, $green, $blue)
	{
		$dbh = $this->get_dbh();
		return $dbh->is_error($dbh->query(preg_replace(
			array('/%u/', '/%l/', '/%n/', '/%r/', '/%g/', '/%b/'),
			array($this->rc->user->ID, $dbh->escape($label), $dbh->escape($name), $red, $green, $blue),
			"UPDATE imap_labels SET name='%n', red=%r, green=%g, blue=%b WHERE userId=%u AND label='%l'"
		)));
	}

	public function label_rename($old, $new)
	{
		$dbh = $this->get_dbh();
		return $dbh->is_error($dbh->query(preg_replace(
			array('/%u/', '/%old/', '/%new/'),
			array($dbh->escape($this->rc->user->ID), $dbh->escape($old), $dbh->escape($new)),
			"UPDATE imap_labels SET label='%new' WHERE userId=%u AND label='%old'"
		)));
	}

	public function get_user_labels()
	{
		$dbh = $this->get_dbh();
		$sql_result = $dbh->query(preg_replace('/%u/', $this->rc->user->ID, 'SELECT label, name, red, green, blue FROM imap_labels WHERE userId = %u'));

		$err = $dbh->is_error($sql_result);
		if ($err) return $err;

		$result = array();
		while ($sql_arr = $dbh->fetch_array($sql_result))
		{
			$result[strtolower($sql_arr[0])] = array(
				'name' => $sql_arr[1],
				'color' => array(
					'red' => $sql_arr[2],
					'green' => $sql_arr[3],
					'blue' => $sql_arr[4],
				)
			);
		}
		return $result;
	}

	public function get_user_label($label)
	{
		$dbh = $this->get_dbh();
		$sql_result = $dbh->query(preg_replace(
			array('/%u/', '/%l/'),
			array($this->rc->user->ID, $dbh->escape($label)),
			'SELECT label, name, red, green, blue FROM imap_labels WHERE userId = %u AND label = \'%l\' LIMIT 1'
		));

		$err = $dbh->is_error($sql_result);
		if ($err) return $err;

		while ($sql_arr = $dbh->fetch_array($sql_result))
		{
			return array(
				'label' => $sql_arr[0],
				'name' => $sql_arr[1],
				'color' => array(
					'red' => $sql_arr[2],
					'green' => $sql_arr[3],
					'blue' => $sql_arr[4],
				),
			);
		}
		return null;
	}

	public function render_button_popup()
	{
		$out = '<div id="imaplabels_button_popup" class="popupmenu"><ul class="toolbarmenu">';

		$labels = $this->get_user_labels();
		if (is_string($labels))
		{
			rcube::write_log('errors', 'Could not fetch imap labels for user ' + $this->rc->user->ID);
			return $out + '</ul></div>';
		}

		foreach ($labels as $label => $labelvalue)
		{
			$out .= '<li id="'.$label.'" class="imaplabel"><a href="#" class="active">'.$labelvalue['name'].'</a></li>';
		}

		$out .= '</ul></div>';
		$this->rc->output->add_gui_object('imaplabels_button_popup', 'imaplabels_button_popup');
		$this->rc->output->add_footer($out);
	}

	function get_dbh()
	{
		return $this->rc->get_dbh();
	}

}

?>
