<?php
define ( 'IN_ECS', true );
require (dirname ( __FILE__ ) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

$act = !empty($_GET['act']) ? trim($_GET['act']) : '';
$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
$page_size = 5;

if($act=='update_password')
{
	$smarty->display('update_user_password.html');
	exit();
}elseif ($act=='get_goods'){
	$smarty->display('get_goods.html');
	exit();
}elseif ($act=='update_user'){
	$smarty->display('update_user.html');
	exit();
	
}elseif($act=='query_user_info'){
	include_once('includes/cls_json.php');
	$card_id = empty($_GET['card_id']) ? '' : substr(trim($_GET['card_id']), 0, 12);
	$json = new JSON;
	$sqlStr = "select uc.user_id, uc.card_state, uc.store_id from ".$ecs->table('user_card')." uc where uc.card_number='".$card_id."'";
	$cardRow = $db->getRow($sqlStr);
	if($cardRow['store_id']==$_SESSION['store']['store_id']){
		if($cardRow['card_state'] == 0){
			die($json->encode(array(code	=>	0)));
		}elseif($cardRow['card_state'] == 2){
			die($json->encode(array(code	=>	2)));
		}
	}else {
		die($json->encode(array(code	=>	3)));
	}
	
	
	$sqlstr = "select u.user_id, u.real_name, u.sex, u.office_phone, u.mobile_phone "
			 ."from ".$ecs->table('users')." as u "
			 ."where u.user_id=".$cardRow['user_id'];
	$cardRow = $db->getRow($sqlstr);
	$test = $json->encode($cardRow);
	die($json->encode($cardRow));
}elseif ($act=='check_mobile'){
	include_once('includes/cls_json.php');
	$mobile = trim($_GET['mobile']);
	$user_id = trim($_GET['user_id']);
	$sqlStr = "select u.user_id from ".$ecs->table('users')." u where u.user_name='".$mobile."' and u.user_id!=".$user_id;
	$mobile = $db->getRow($sqlStr);
	$json = new JSON;
	die($json->encode($mobile));
}elseif ($act=='user_account'){
    $smarty->display('user_transaction.html');
	exit();
}elseif ($act=='get_accounts'){
	include_once(ROOT_PATH . 'includes/lib_clips.php');
    
	$card_no =!empty($_POST['card_no']) ? trim($_POST['card_no']) : '';
	if(empty($card_no)){
		$card_no = $_REQUEST['card_no'];
	}
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    
	
	/*获取会员id*/
	$sql = "SELECT user_id FROM ".$ecs->table('user_card')."where card_number ='$card_no'";
	$user_id = $db->getOne($sql);
	
	/* 获取记录条数 */
	$sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
	" WHERE user_id = '$user_id'" .
	" AND process_type " . db_create_in(array('0', '1'));
	$record_count = $db->getOne($sql);
	
	//分页函数
	$pager = get_pager('shop_user.php', array('act' => $action), $record_count, $page , $size =5);
	
	if($pager['page']>1){
		$pager['pre'] =$pager['page']-1;
	}
	if($pager['page'] < $pager['page_count']){
		$pager['next'] =$pager['page']+1;
	}

	//获取剩余余额
	$surplus_amount = get_user_surplus($user_id);
	if (empty($surplus_amount))
	{
		$surplus_amount = 0;
	}
	
	//获取余额记录
	$account_log = get_account_log($user_id, $pager['size'], $pager['start']);
	
	//模板赋值
	$smarty->assign('lang',       $_LANG);
	$smarty->assign('card_no',    $card_no);
	$smarty->assign('action',       'account_log');
	$smarty->assign('surplus_amount', price_format($surplus_amount, false));
	$smarty->assign('account_log',    $account_log);
	$smarty->assign('pager',          $pager);
	$smarty->display('user_accounts.html');
	exit();
}
elseif ($act=='update_user_submit'){
	$user_id = trim($_GET['user_id']);
	$real_name = trim($_POST['name']);
	$sex = $_POST['sex'];
	$office_phone = trim($_POST['phone']);
	/*
	$sqlStr = "select u.user_id from ".$ecs->table('users')." u where u.user_name='".$office_phone."' and u.user_id!=".$user_id;
	$user = $db->getRow($sqlStr);
	if(!empty($user)){
		show_msg ( "此号码".$office_phone."已被注册!" , "继续修改", "shop_user.php?act=update_user");
	}
	*/
	$filter = " u.real_name='".$real_name."', u.sex='".$sex."', u.office_phone='".$office_phone."', u.mobile_phone='".$office_phone."'";
	if(!empty($office_phone)){
		$filter .= ", u.user_name='".$office_phone."'";
	}
	$sqlStr = "update ".$ecs->table('users')." as u set ".$filter
		." where u.user_id=".$user_id;
	$db->query($sqlStr);
	show_msg ( "资料修改成功!" , "返回会员管理", "shop_user.php");
}else {
	$smarty->display('user_space.html');
	exit();
}
