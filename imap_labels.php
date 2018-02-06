<?php

class imap_labels extends rcube_plugin
{

	function init()
	{
		$rcube = rcube::get_instance();

		switch ($rcube->task)
		{
			case 'mail':
				$this->include_script('imap_labels.js');
				$this->add_hook('messages_list', array($this, 'read_flags'));
				break;
		}
	}

	public function read_flags($args)
	{
		if (!isset($args['messages']) or !is_array($args['messages']))
			return $args;

		$rcube = rcube::get_instance();
		$knownflags = $this->get_user_labels($rcube->user-ID);
		$rcube->output->set_env('imap_label_colors', $knownflags);
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

	function get_user_labels($userId)
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
		return rcube::get_instance()->get_dbh();
	}

}

?>