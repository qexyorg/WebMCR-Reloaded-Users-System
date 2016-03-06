<?php

define('MCR', '');

require_once("../system.php");

require_once(MCR_ROOT."install_us/language/".$core->config->main['s_lang']."/install.php");

$core->lng_m = $lng;

require_once(MCR_CONF_PATH.'modules/users.php');

$core->cfg_m = $cfg;

$core->def_header = $core->sp(MCR_ROOT."install_us/theme/header.html");

$mode = (isset($_GET['mode'])) ? $_GET['mode'] : 'step_1';

if(!$core->cfg_m['install'] && $mode!='finish'){ $core->notify('','', 3, '?mode=finish'); }

switch($mode){
	case 'step_1':
	case 'step_2':
	case 'step_3':

		require_once(MCR_ROOT.'install_us/'.$mode.'.php');
		$module = new module($core);
		$content = $module->content();

	break;

	case 'finish':
		$core->bc = $core->gen_bc(array($lng['mod_name'] => ''));
		$content = $core->sp(MCR_ROOT."install_us/theme/finish.html");
		if(isset($_SESSION['step_1'])){ unset($_SESSION['step_1']); }
		if(isset($_SESSION['step_2'])){ unset($_SESSION['step_2']); }
		if(isset($_SESSION['step_3'])){ unset($_SESSION['step_3']); }
	break;

	default:
		$content = $core->notify($lng['mod_name'], 'Шаг #1', 4, '?mode=step_1');
	break;
}

function load_left_block($core, $mode){
	$array = array(
		"step_1" => $core->lng_m['step_1'],
		"step_2" => $core->lng_m['step_2'],
		"step_3" => $core->lng_m['step_3'],
		"finish" => $core->lng_m['finish']
	);

	ob_start();

	foreach($array as $key => $value) {

		if($mode==$key){
			echo '<li class="active"><a href="javascript://">'.$value.'</a></li>';
		}else{
			echo '<li class="muted">'.$value.'</li>';
		}
	}

	$data['ITEMS'] = ob_get_clean();

	include(MCR_SIDE_PATH."1_notify.php");

	require_once(MCR_LANG_DIR.'blocks/notify.php');
	$core->lng_b = $lng;

	$notify = new block_notify($core);

	return $notify->content().$core->sp(MCR_ROOT."install_us/theme/left-block.html", $data);
}

$data_global = array(
	"CONTENT"		=> $content,
	"TITLE"			=> $core->title,
	"L_BLOCKS"		=> load_left_block($core, $mode),
	"HEADER"		=> $core->header,
	"DEF_HEADER"	=> $core->def_header,
	"CFG"			=> $core->config->main,
	"ADVICE"		=> '',//$core->advice(),
	"MENU"			=> '',//$core->menu->_list(),
	"BREADCRUMBS"	=> $core->bc,
	"SEARCH"		=> ''
);

// Write global template
echo $core->sp(MCR_THEME_PATH."global.html", $data_global);


?>