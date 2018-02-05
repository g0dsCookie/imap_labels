<?php

class imap_labels extends rcube_plugin
{

	private $app;

	function init()
	{
		$this->app = rcmail::get_instance();

		if ($this->app->action == 'print') return;

		$this->app->output->set_env('imap_label_colors', $this->get_user_labels($this->app->user->ID));

		$this->include_script('imap_labels.js');
		$this->add_hook('messages_list', array($this, 'read_flags'));

		$this->name = get_class($this);
	}

	public function read_flags($args)
	{
		if (!isset($args['messages']) or !is_array($args['messages']))
			return $args;

		$knownflags = $this->get_user_labels($this->app->user-ID);
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
		return $this->app->get_dbh();
	}

}

?>