<?php

class imap_labels extends rcube_plugin {
	public $task = 'mail';
	private $rc;
	private $map;

	function init() {
		$this->rc = rcmail::get_instance();
		$this->load_config();
		$this->add_texts('localization/', false);

		if ($this->rc->action == 'print')
			return;

		$this->rc->output->set_env('imap_label_colors', $this->rc->config->get('imap_known_labels'));

		$this->include_script('imap_labels.js');
		$this->include_stylesheet('imap_labels.css');
		$this->add_hook('messages_list', array($this, 'read_flags'));

		$this->name = get_class($this);
		$this->message_imap_labels = array();
	}

	public function read_flags($args) {
		if (!isset($args['messages']) or !is_array($args['messages']))
			return $args;

		$knownflags = $this->rc->config->get('imap_known_labels');
		if (!is_array($knownflags))
			return $args; # return if no known flags are defined

		foreach ($args['messages'] as $message) {
			$message->list_flags['extra_flags']['imap_labels'] = array();
			if (is_array($message->flags)) {
				foreach ($message->flags as $flagname => $flagvalue) {
					$flag = is_numeric("$flagvalue") ? $flagname : $flagvalue;
					$flag = strtolower($flag);
					foreach ($knownflags as $knownflag => $flagcolor) {
						if ($flag == $knownflag)
							$message->list_flags['extra_flags']['imap_labels'][] = $flag;
					}
				}
			}
		}

		return $args;
	}

	public function imap_label_button() {
		$knownflags = $this->rc->config->get('imap_known_labels');
		$out = '<div id="imap_label_button" class="buttonmenu"><ul class="toolbarmenu">';

		foreach ($knownflags as $flagname => $flagvalue) {
			$out .= '<li class="'..'"></li>'
		}

		$out .= '</ul></div>';
	}
}