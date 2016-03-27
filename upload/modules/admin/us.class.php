<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
	private $core, $db, $config, $user, $lng, $cfg_m;

	public function __construct($core){
		$this->core = $core;
		$this->db	= $core->db;
		$this->config = $core->config;
		$this->user	= $core->user;
		$this->lng	= $core->lng_m;

		require_once(MCR_LANG_DIR.'users.php');

		$core->lng_m = $lng;

		require_once(MCR_CONF_PATH.'modules/users.php');

		$this->cfg_m = $cfg;

		if(!$this->core->is_access('mod_users_adm_settings')){ $this->core->notify($this->core->lng['403'], $this->core->lng['e_403']); }

		$bc = array(
			$this->lng['mod_name'] => BASE_URL."?mode=admin",
			$core->lng_m['mod_name_cp'] => BASE_URL."?mode=admin&do=us"
		);

		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function main(){

		$cfg = $this->cfg_m;

		if($_SERVER['REQUEST_METHOD']=='POST'){

			$cfg['enable_comments']			= (intval(@$_POST['use_comments']) === 1) ? true : false;

			$cfg['users_on_page']			= (intval(@$_POST['users_on_page']) < 1) ? 1 : intval(@$_POST['users_on_page']);

			$cfg['comments_on_page']		= (intval(@$_POST['comments_on_page']) < 1) ? 1 : intval(@$_POST['users_on_page']);

			if(!$this->config->savecfg($cfg, 'modules/users.php', 'cfg')){ $this->core->notify($this->core->lng["e_msg"], $this->core->lng_m['set_e_cfg_save'], 2, '?mode=admin&do=settings'); }

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->core->lng_m['log_set_main_save'], $this->user->id);
			
			$this->core->notify($this->core->lng["e_success"], $this->core->lng_m['set_save_success'], 3, '?mode=admin&do=us');
		}

		$data = array(
			"CFG"			=> $cfg,
			"USE_COMMENTS"	=> ($cfg['enable_comments']) ? 'selected' : '',
		);

		return $this->core->sp(MCR_THEME_MOD."admin/us/main.html", $data);
	}

	public function content(){

		return $this->main();
	}
}

?>