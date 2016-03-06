<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
	private $core, $db, $config, $user, $lng, $cfg_m;

	public function __construct($core){
		$this->core = $core;
		$this->db	= $core->db;
		$this->config = $core->config;
		$this->user	= $core->user;

		require_once(MCR_LANG_DIR.'users.php');

		$this->lng = $lng;
		$this->core->lng_m = $lng;

		require_once(MCR_CONF_PATH.'modules/users.php');

		$this->cfg_m = $cfg;
	}

	private function typeahead(){

		$login = $this->db->safesql($_GET['query']); // only latin1

		$query = $this->db->query("SELECT login FROM `mcr_users`
									WHERE login LIKE '%$login%'
									ORDER BY login ASC
									LIMIT 10");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->js_notify('Empty', 'Empty'); }

		$data = array();

		while($ar = $this->db->fetch_assoc($query)){
			$data[] = $this->db->HSC($ar['login']);
		}

		$this->core->js_notify('success', 'success', true, $data);
	}

	private function add_comment(){

		if($_SERVER['REQUEST_METHOD']!='POST'){ $this->core->js_notify($this->core->lng['e_hack']); }

		if(!$this->core->is_access('mod_users_comment_add') || !$this->cfg_m['enable_comments']){ $this->core->js_notify($this->core->lng['403']); }

		$login = $this->db->safesql(@$_POST['login']);

		$query = $this->db->query("SELECT id FROM `mcr_users` WHERE login='$login'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->js_notify($this->core->lng['403']); }

		$ar = $this->db->fetch_assoc($query);

		$uid = intval($ar['id']);

		$message = @$_POST['message'];

		$message_trim = trim($message);

		if(empty($message_trim)){ $this->core->js_notify($this->lng['com_msg_empty']); }

		if(isset($_SESSION['add_comment'])){
			if(intval($_SESSION['add_comment'])>time()){
				$expire = intval($_SESSION['add_comment'])-time();
				$this->core->js_notify($this->lng['com_wait']." $expire ".$this->lng['com_wait1']);
			}else{
				$_SESSION['add_comment'] = time()+30;
			}
		}else{
			$_SESSION['add_comment'] = time()+30;
		}

		$bb = $this->core->load_bb_class(); // Object

		$text_html		= $bb->parse($message);
		$safe_text_html	= $this->db->safesql($text_html);

		$text_bb		= $this->db->safesql($message);

		$message_strip = trim(strip_tags($text_html, "<img>"));

		if(empty($message_strip)){ $this->core->js_notify($this->lng['com_msg_empty']); }

		$newdata = array(
			"date_create" => time(),
			"date_update" => time()
		);

		$safedata = $this->db->safesql(json_encode($newdata));

		$insert = $this->db->query("INSERT INTO `mod_users_comments`
										(uid, `from`, text_html, text_bb, `data`)
									VALUES
										('$uid', '{$this->user->id}', '$safe_text_html', '$text_bb', '$safedata')");

		if(!$insert){ $this->core->js_notify($this->core->lng['e_sql_critical']); }

		$id = $this->db->insert_id();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->lng['log_com_add']." #$login", $this->user->id);

		$admin = '';

		if($this->core->is_access('mod_users_comment_del') || $this->core->is_access('mod_users_comment_del_all')){
			$admin = $this->core->sp(MCR_THEME_MOD."users/comments/comment-admin.html");
		}

		$com_data	= array(
			"ID"				=> $id,
			"TEXT"				=> $text_html,
			"DATE_CREATE"		=> date('d.m.Y '.$this->lng['in'].' H:i'),
			"LOGIN"				=> $this->user->login_v2,
			'ADMIN'				=> $admin,
		);

		$content = $this->core->sp(MCR_THEME_MOD."users/comments/comment-id-self.html", $com_data);

		$this->core->js_notify($this->lng['com_add_success'], $this->core->lng['e_success'], true, $content);
	}

	private function del_comment(){
		if($_SERVER['REQUEST_METHOD']!='POST'){ $this->core->js_notify($this->core->lng['e_hack']); }

		if(!$this->core->is_access('mod_users_comment_del') && !$this->core->is_access('mod_users_comment_del_all')){
			$this->core->js_notify($this->core->lng['403']);
		}

		$id = intval(@$_POST['id']);

		$query = $this->db->query("SELECT uid, `from` FROM `mod_users_comments` WHERE id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->js_notify($this->lng['com_not_found']); }

		$ar = $this->db->fetch_assoc($query);

		if(intval($ar['uid'])!=$this->user->id && intval($ar['from'])!=$this->user->id && !$this->core->is_access('mod_users_comment_del_all')){
			$this->core->js_notify($this->core->lng['403']);
		}

		$delete = $this->db->query("DELETE FROM `mod_users_comments` WHERE id='$id'");

		if(!$delete){ $this->core->js_notify($this->core->lng['e_sql_critical']); }

		$this->core->js_notify($this->lng['com_del_success'], $this->core->lng['e_success'], true);
	}

	public function content(){

		//if($_SERVER['REQUEST_METHOD']!='POST'){ $this->core->js_notify($this->core->lng['e_hack']); }

		$act = (isset($_GET['act'])) ? $_GET['act'] : '';

		switch($act){
			case 'typeahead': $this->typeahead(); break;
			case 'add_comment': $this->add_comment(); break;
			case 'del_comment': $this->del_comment(); break;

			default: $this->core->js_notify($this->core->lng['e_hack']); break;
		}
	}

}

?>