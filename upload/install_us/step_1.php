<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $config, $lng, $lng_m, $user;

	public function __construct($core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->user		= $core->user;
		$this->config	= $core->config;
		$this->lng		= $core->lng;
		$this->lng_m	= $core->lng_m;

		$this->core->title = $this->lng_m['mod_name'].' — '.$this->lng_m['step_1'];

		$bc = array(
			$this->lng_m['mod_name'] => BASE_URL."install_us/",
			$this->lng_m['step_1'] => BASE_URL."install_us/?mode=step_1"
		);

		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function check_write_all($folder){
		if(!is_writable($folder) || !is_readable($folder)){ return false; }

		$scan = scandir($folder);

		$result = true;

		foreach($scan as $key => $value) {
			if($value=='.' || $value=='..'){ continue; }

			$path = $folder.'/'.$value;

			if(!is_writable($path) || !is_readable($path)){ $result = false; }
		}

		return $result;
	}

	public function content(){
		if(isset($_SESSION['step_1'])){ $this->core->notify('', '', 4, '?mode=step_2'); }

		if($_SERVER['REQUEST_METHOD']=='POST'){

			if(!is_writable(MCR_ROOT.'configs/modules/users.php') || !is_readable(MCR_ROOT.'configs/modules/users.php')){ $this->core->notify($this->lng['e_msg'], $this->lng_m['e_perm_config'], 2, '?mode=step_1'); }

			$_SESSION['step_1'] = true;

			$this->core->notify($this->lng_m['mod_name'], $this->lng_m['step_2'], 4, '?mode=step_2');

		}

		$data = array(

			"CONFIG" => (is_writable(MCR_ROOT.'configs/modules/users.php') && is_readable(MCR_ROOT.'configs/modules/users.php')) ? '<b class="text-success">'.$this->lng['yes'].'</b>' : '<b class="text-error">'.$this->lng['no'].'</b>',
		);

		return $this->core->sp(MCR_ROOT."install_us/theme/step_1.html", $data);
	}

}

?>