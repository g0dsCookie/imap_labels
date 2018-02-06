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
				$this->include_script('imap_labels.js');
				$this->add_hook('messages_list', array($this, 'messages_list'));
				break;
			case 'settings':
				$this->register_action('plugin.imap_labels-settings', array($this, 'imap_labels_settings'));
				$this->add_hook('settings_actions', array($this, 'settings_actions'));
				$this->init_ui();
				break;
		}
	}

	function init_ui()
	{
		if ($this->ui_initialized) return;

		$this->add_texts('localization/', true);

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
		));

		$this->rc->output->set_pagetitle($this->gettext('imap_labels'));
		$this->rc->output->send('imap_labels.imap_labels');
	}

	public function imaplabels_list($attrib)
	{
		if (!strlen($attrib['id']))
			$attrib['id'] = 'rcmimaplabelslist';

		$labels = $this->get_user_labels($this->rc->user->ID);
		$this->rc->output->set_env('imap_label_colors', $labels);

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

		return $out;
	}

	public function messages_list($args)
	{
		if (!isset($args['messages']) or !is_array($args['messages']))
			return $args;

		$knownflags = $this->get_user_labels($this->rc->user-ID);
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
	}

	public function get_user_labels($userId)
	{
		$dbh = $this->get_dbh();
		$sql_result = $dbh->query(preg_replace('/%u/', $dbh->escape($userId), 'SELECT label, name, red, green, blue FROM imap_labels WHERE userId = %u'));

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

	function get_dbh()
	{
		return $this->rc->get_dbh();
	}

}

?>