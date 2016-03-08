<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $config, $user, $lng;
	public $cfg;

	public function __construct($core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->config	= $core->config;
		$this->user		= $core->user;
		$this->lng		= $core->lng_m;

		$bc = array(
			$this->lng['mod_name'] => BASE_URL."?mode=users"
		);

		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function user_array($search='', $gid=''){
		$end = $this->cfg['users_on_page'];
		$start = $this->core->pagination($end, 0, 0); // Set start pagination
		$where = "";

		if(!empty($gid)){
			$gid2 = intval($gid);
			$where .= " WHERE `u`.gid='$gid2'";
		}

		if(!empty($search)){
			$searchstr = $this->db->safesql(urldecode($search));
			if(!preg_match("/[а-яА-ЯёЁ]+/iu", $searchstr)){
				$where .= (!empty($gid)) ? " AND " : " WHERE ";
				$where .= "`u`.login LIKE '%$searchstr%'";
			}
		}

		$query = $this->db->query("SELECT `u`.gid, `u`.`color`, `u`.login, `u`.is_skin, `u`.is_cloak, `u`.`data`,
											`g`.`title` AS `group`, `g`.`color` AS `gcolor`
									FROM `mcr_users` AS `u`
									LEFT JOIN `mcr_groups` AS `g`
										ON `g`.id=`u`.gid
									$where
									ORDER BY `u`.id DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->core->sp(MCR_THEME_MOD."users/user-none.html").$this->db->error(); }

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){

			$color = (!empty($ar['color'])) ? $this->db->HSC($ar['color']) : $this->db->HSC($ar['gcolor']);

			$gcolor = $this->db->HSC($ar['gcolor']);

			$login = $this->db->HSC($ar['login']);
			$group = $this->db->HSC($ar['group']);

			$json = json_decode($ar['data'], true);

			$date_reg = date('d.m.Y '.$this->lng['in'].' H:i', @$json['time_create']);

			$gender = (intval($json['gender'])==1) ? $this->core->lng['gender_w'] : $this->core->lng['gender_m'];

			$is_girl = (intval($json['gender'])==1) ? 'default_mini_female.png' : 'default_mini.png';

			$avatar = (intval($ar['is_skin'])==1) ? $login.'_mini.png' : $is_girl;

			$url = BASE_URL.'?mode=users&uid='.$login;
			$gurl = BASE_URL.'?mode=users&gid='.intval($ar['gid']);

			$data = array(
				'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'?'.mt_rand(1000,9999),
				'LOGIN' => $this->core->colorize($login, $color, '<a href="'.$url.'" style="color: {COLOR};">{STRING}</a>'),
				'GROUP' => $this->core->colorize($group, $gcolor, '<a href="'.$gurl.'" style="color: {COLOR};">{STRING}</a>'),
				'URL' => $url,
				'REGISTERED' => $date_reg,
				'GENDER' => $gender,
			);

			echo $this->core->sp(MCR_THEME_MOD."users/user-id.html", $data);
		}

		return ob_get_clean();
	}

	private function user_list($search='', $gid=''){
		if(!$this->core->is_access('mod_users_list')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }

		$page = '?mode=users'; // for sorting
		$sql = "SELECT COUNT(*) FROM `mcr_users`"; // for sorting

		if(!empty($gid)){
			$gid2 = intval($gid);
			$page .= '&gid='.$gid2;
			$sql .= " WHERE gid='$gid2'";
		}

		if(!empty($search)){
			$srch = urldecode($search);
			if(!preg_match("/[а-яА-ЯёЁ]+/iu", $srch)){
				$page .= '&search='.$this->db->HSC($srch);
				$searchstr = $this->db->safesql($srch);
				$sql .= (!empty($gid)) ? " AND " : " WHERE ";
				$sql .= "login LIKE '%$searchstr%'";
			}
		}

		$query = $this->db->query($sql);

		if(!$query){ exit("SQL Error"); }

		$ar = $this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg['users_on_page'], $page.'&pid=', $ar[0]),
			"USERS" => $this->user_array($search, $gid)
		);

		return $this->core->sp(MCR_THEME_MOD."users/user-list.html", $data);
	}

	private function user_full(){
		if(!$this->core->is_access('mod_users_full')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }

		$bc = array(
			$this->lng['mod_name'] => BASE_URL."?mode=users",
			$this->lng['user_profile'] => ''
		);

		$this->core->bc = $this->core->gen_bc($bc);

		$login = $this->db->safesql($_GET['uid']);

		$query = $this->db->query("SELECT `u`.id, `u`.gid, `u`.`color`, `u`.login, `u`.is_skin, `u`.is_cloak, `u`.`data`,
											`g`.`title` AS `group`, `g`.`color` AS `gcolor`,
											`i`.`money`, `i`.realmoney
									FROM `mcr_users` AS `u`
									LEFT JOIN `mcr_groups` AS `g`
										ON `g`.id=`u`.gid
									LEFT JOIN `mcr_iconomy` AS `i`
										ON `i`.login=`u`.login
									WHERE `u`.login='$login'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->notify($this->core->lng['404'], $this->lng['user_not_found'], 2, "?mode=users"); }

		$ar = $this->db->fetch_assoc($query);

		$json = json_decode($ar['data'], true);

		$color = (!empty($ar['color'])) ? $this->db->HSC($ar['color']) : $this->db->HSC($ar['gcolor']);

		$gcolor = $this->db->HSC($ar['gcolor']);
		$group = $this->db->HSC($ar['group']);

		$date_reg = date('d.m.Y '.$this->lng['in'].' H:i', @$json['time_create']);
		$date_last = date('d.m.Y '.$this->lng['in'].' H:i', @$json['time_last']);

		$is_skin = (intval($ar['is_skin'])==1) ? true : false;
		$is_cloak = (intval($ar['is_cloak'])==1) ? true : false;

		$gender = (intval($json['gender'])==1) ? $this->core->lng['gender_w'] : $this->core->lng['gender_m'];

		$is_girl = (intval($json['gender'])==1) ? 'default_female' : 'default';

		$avatar = ($is_skin || $is_cloak) ? $this->db->HSC($login) : $is_girl;

		$data = array(
			'LOGIN' => $this->core->colorize($login, $color),
			'GROUP' => $this->core->colorize($group, $gcolor),
			'MONEY' => floatval(@$ar['money']), // @ because money can be null
			'REALMONEY' => floatval(@$ar['realmoney']), // @ because realmoney can be null
			'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'.png?'.mt_rand(1000,9999),
			'DATE_REG' => $date_reg,
			'DATE_LAST' => $date_last,
			'GENDER' => $gender,
			'ADMIN' => '',
			'COMMENTS' => $this->comment_list($ar['id']),
		);

		return $this->core->sp(MCR_THEME_MOD."users/user-full.html", $data);
	}

	private function comment_array($uid){
		$end = $this->cfg['comments_on_page'];
		$start = $this->core->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("SELECT `c`.id, `c`.`from`, `c`.text_html, `c`.`data`, `u`.login, `u`.`color`, `g`.`color` AS `gcolor`
									FROM `mod_users_comments` AS `c`
									LEFT JOIN `mcr_users` AS `u`
										ON `u`.id=`c`.`from`
									LEFT JOIN `mcr_groups` AS `g`
										ON `g`.id=`u`.id
									WHERE `c`.uid='$uid'
									ORDER BY `c`.id DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return; }

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){

			$json = json_decode($ar['data'], true);

			$color = (!empty($ar['color'])) ? $this->db->HSC($ar['color']) : $this->db->HSC($ar['gcolor']);

			$admin = '';

			if($this->core->is_access('mod_users_comment_del') || $this->core->is_access('mod_users_comment_del_all')){
				$admin = $this->core->sp(MCR_THEME_MOD."users/comments/comment-admin.html");
			}

			$data = array(
				'ID' => intval($ar['id']),
				'LOGIN' => $this->core->colorize($this->db->HSC($ar['login']), $color),
				'TEXT' => $ar['text_html'],
				'DATE_CREATE' => date('d.m.Y '.$this->lng['in'].' H:i', @$json['date_create']),
				'ADMIN' => $admin,
			);

			if($this->user->id == intval($ar['from'])){
				echo $this->core->sp(MCR_THEME_MOD."users/comments/comment-id-self.html", $data);
			}else{
				echo $this->core->sp(MCR_THEME_MOD."users/comments/comment-id.html", $data);
			}
		}

		return ob_get_clean();
	}

	private function comment_form(){
		if(!$this->cfg['enable_comments'] || !$this->core->is_access('mod_users_comment_add')){ return; }

		$bb = $this->core->load_bb_class();

		$data['BB_PANEL'] = $bb->bb_panel('bb-comments');

		return $this->core->sp(MCR_THEME_MOD."users/comments/comment-form.html", $data);
	}

	private function comment_list($uid){
		if(!$this->core->is_access('mod_users_comments')){ return; }

		$uid = intval($uid);

		$query = $this->db->query("SELECT COUNT(*) FROM `mod_users_comments` WHERE uid='$uid'");

		if(!$query){ exit("SQL Error"); }

		$ar = $this->db->fetch_array($query);

		$page = '?mode=users&uid='.$this->db->HSC($_GET['uid']).'&pid=';

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg['comments_on_page'], $page, $ar[0]),
			"COMMENTS" => $this->comment_array($uid),
			"COMMENT_FORM" => $this->comment_form(),
		);

		return $this->core->sp(MCR_THEME_MOD."users/comments/comment-list.html", $data);
	}

	public function content(){

		if($this->cfg['install']){

			if(!$this->core->is_access('sys_adm_main')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }
			$this->core->notify($this->core->lng['e_attention'], $this->lng['need_install'], 4, 'install_us/');
		}

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."users/header.html");

		if(isset($_GET['uid'])){
			return $this->user_full();
		}

		return $this->user_list(@$_GET['search'], @$_GET['gid']);
	}
}

?>