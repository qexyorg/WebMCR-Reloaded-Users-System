<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $cfg, $lng, $lng_m, $user;

	public function __construct($core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->user		= $core->user;
		$this->cfg		= $core->cfg;
		$this->lng		= $core->lng;
		$this->lng_m	= $core->lng_m;

		$this->core->title = $this->lng_m['mod_name'].' — '.$this->lng_m['step_2'];

		$bc = array(
			$this->lng_m['mod_name'] => BASE_URL."install_us/",
			$this->lng_m['step_2'] => BASE_URL."install_us/?mode=step_2"
		);

		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content(){
		if(!isset($_SESSION['step_1'])){ $this->core->notify('', '', 4, '?mode=step_1'); }
		if(isset($_SESSION['step_2'])){ $this->core->notify('', '', 4, '?mode=step_3'); }

		if($_SERVER['REQUEST_METHOD']=='POST'){

			$this->core->cfg_m['enable_comments'] = (intval(@$_POST['use_comments'])==1) ? true : false;
			$this->core->cfg_m['users_on_page'] = (intval(@$_POST['rop_users'])<1) ? 1 : intval(@$_POST['rop_users']);
			$this->core->cfg_m['comments_on_page'] = (intval(@$_POST['rop_comments'])<1) ? 1 : intval(@$_POST['rop_comments']);

			if(!$this->cfg->savecfg($this->core->cfg_m, 'modules/users.php', 'cfg')){
				$this->core->notify($this->lng['e_msg'], $this->lng_m['e_settings'], 2, '?mode=step_2');
			}

			$_SESSION['step_2'] = true;

			$this->core->notify($this->lng_m['mod_name'], $this->lng_m['step_2'], 4, '?mode=step_3');

		}

		$data = array(
			"COMMENTS" => ($this->core->cfg_m['enable_comments']) ? 'selected' : '',
		);

		return $this->core->sp(MCR_ROOT."install_us/theme/step_2.html", $data);
	}

}

?>