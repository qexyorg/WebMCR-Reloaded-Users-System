<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $cfg_m, $user, $lng;

	public function __construct($core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->cfg		= $core->cfg_m;
		$this->user		= $core->user;
		$this->lng		= $core->lng_m;

		$bc = array(
			$this->lng['mod_name'] => BASE_URL."?mode=refer"
		);

		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function user_array(){
		$end		= $this->cfg['MOD_ROP'];
		$start		= $this->core->pagination($end, 0, 0); // Set start pagination

		$ctables	= $this->core->cfg->db['tables'];
		$us_f		= $ctables['users']['fields'];
		$us_g		= $ctables['ugroups']['fields'];

		$query = $this->db->query("SELECT `r`.`id`, `r`.`date`, `u`.`{$us_f['login']}`, `u`.`{$us_f['gender']}`,
									`u`.`{$us_f['is_skin']}`, `u`.`{$us_f['date_reg']}`, `u`.`{$us_f['color']}`,
									`u`.`{$us_f['group']}`,
									`ug`.`{$us_g['title']}` AS `group`, `ug`.`{$us_g['color']}` AS `gcolor`
									FROM `mod_ref_users` AS `r`
									LEFT JOIN `{$this->core->cfg->tabname('users')}` AS `u`
										ON `u`.`{$us_f['id']}`=`r`.`uid`
									LEFT JOIN `{$this->core->cfg->tabname('ugroups')}` AS `ug`
										ON `ug`.`{$us_g['id']}`=`u`.`{$us_f['group']}`
									WHERE `r`.`status`='1' AND `r`.`uid`='{$this->user->id}'
									ORDER BY `r`.`id` DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->core->sp(MCR_THEME_MOD."refer/user-none.html").$this->db->error(); }

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){

			$login = $this->db->HSC($ar[$us_f['login']]);

			$gender = (intval($ar[$us_f['gender']])==1) ? $this->core->lng['gender_w'] : $this->core->lng['gender_m'];
			$is_girl = (intval($ar[$us_f['gender']])==1) ? 'default_mini_female.png' : 'default_mini.png';
			$avatar = (intval($ar[$us_f['is_skin']])==1) ? $login.'_mini.png' : $is_girl;
			$date_reg = date('d.m.Y H:i', intval(@$ar[$us_f['date_reg']]));

			$gcolor = $this->db->HSC($ar['gcolor']);

			$group = $this->db->HSC($ar['group']);

			$color = (!empty($ar[$us_f['color']])) ? $this->db->HSC($ar[$us_f['color']]) : $gcolor;

			$url = BASE_URL.'?mode=users&uid='.$login;
			$gurl = BASE_URL.'?mode=users&gid='.intval($ar[$us_f['group']]);

			$data = array(
				'ID' => intval($ar['id']),
				'LOGIN' => $this->core->colorize($login, $color, '<a href="'.$url.'" style="color: {COLOR};">{STRING}</a>'),
				'GROUP' => $this->core->colorize($group, $gcolor, '<a href="'.$gurl.'" style="color: {COLOR};">{STRING}</a>'),
				'DATE' => date('d.m.Y - H:i:s', $ar['date']),
				'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'?'.mt_rand(1000,9999),
				'GENDER' => $gender,
				'REGISTERED' => $date_reg,
				'URL' => $url,
			);

			echo $this->core->sp(MCR_THEME_MOD."refer/user-id.html", $data);
		}

		return ob_get_clean();
	}

	private function user_list(){
		if(!$this->core->is_access('mod_refer_list')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }

		// Проверка и выдача вознаграждения
		$this->user_gift();

		$query = $this->db->query("SELECT COUNT(*) FROM `mod_ref_users` WHERE `uid`='{$this->user->id}'");

		if(!$query){ $this->core->notify($this->core->lng['e_msg'], $this->core->lng['e_sql_critical'], 2); }

		$ar = $this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg['MOD_ROP'], '?mode=refer&pid=', $ar[0]),
			"USERS" => $this->user_array(),
			"LINK" => $this->core->cfg->main['s_root_full'].'?mode=refer&by='.$this->user->id,
		);

		return $this->core->sp(MCR_THEME_MOD."refer/user-list.html", $data);
	}

	private function user_gift(){

		$ctables	= $this->core->cfg->db['tables'];
		$us_f		= $ctables['users']['fields'];
		$ui_f		= $ctables['iconomy']['fields'];

		$query = $this->db->query("SELECT COUNT(*) FROM `mod_ref_users` WHERE `status`='0' AND uid='{$this->user->id}'");
		if(!$query){ return; }
		$ar = $this->db->fetch_array($query);
		if($ar[0]<=0){ return; }

		$prize = $this->cfg['MOD_PRIZE']*intval($ar[0]);

		$type = ($this->cfg['MOD_PRIZE_TYPE']==1) ? $ui_f['rm'] : $ui_f['money'];

		$update = $this->db->query("UPDATE `mod_ref_users` SET `status`='1' WHERE `status`='0' AND uid='{$this->user->id}'");

		if(!$update){ return; }

		$update = $this->db->query("UPDATE `{$this->core->cfg->tabname('iconomy')}`
									SET `{$type}`=`{$type}`+$prize
									WHERE `login`='{$this->user->login}'");

		if(!$update){ return; }

		return;//$this->core->notify($this->core->lng['e_success'], $this->lng['new_refer'], 3, "?mode=refer");
	}

	private function user_by(){
		$ctables	= $this->core->cfg->db['tables'];
		$us_f		= $ctables['users']['fields'];

		$time		= time();

		if(!isset($_GET['by'])){ $this->core->notify(); }

		$uid = (intval($_GET['by'])<0 || intval($_GET['by'])>2147483647) ? 0 : intval($_GET['by']);

		// Проверка ранее зарегистрированных пользователей +
		$query = $this->db->query("SELECT COUNT(*) FROM `{$this->core->cfg->tabname('users')}` WHERE `{$us_f['ip_create']}`='{$this->user->ip}' OR `{$us_f['ip_last']}`='{$this->user->ip}'");
		if(!$query){ $this->core->notify(); }
		$ar = $this->db->fetch_array($query);
		if($ar[0]>0){ $this->core->notify(); }
		// Проверка ранее зарегистрированных пользователей -

		// Проверка на использованность реф.ссылки +
		$query = $this->db->query("SELECT COUNT(*) FROM `mod_ref_users` WHERE ip='{$this->user->ip}'");
		if(!$query){ $this->core->notify(); }
		$ar = $this->db->fetch_array($query);
		if($ar[0]>0){ $this->core->notify(); }
		// Проверка на использованность реф.ссылки -

		// Добавление реферала в список +
		$insert = $this->db->query("INSERT INTO `mod_ref_users`
										(ip, uid, `date`)
									VALUES
										('{$this->user->ip}', '$uid', '$time')");

		if(!$insert){ $this->core->notify($this->core->lng['e_msg'], $this->core->lng['e_sql_critical'], 2); }
		// Добавление реферала в список -

		$this->core->notify();
	}

	public function content(){

		if($this->cfg['MOD_INSTALL']){

			if(!$this->core->is_access('sys_adm_main')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }
			$this->core->notify($this->core->lng['e_attention'], $this->lng['need_install'], 4, 'install_refer/');
		}

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."refer/header.html");

		return (isset($_GET['by'])) ? $this->user_by() : $this->user_list();
	}
}

?>
