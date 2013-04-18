<?php

/**
 * ECSHOP 会员中心
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: user.php 17067 2010-03-26 03:59:37Z liuhui $
 */

define ( 'IN_ECS', true );

require (dirname ( __FILE__ ) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG ['lang'] . '/user.php');

$user_id = $_SESSION ['user_id'];
$action = isset ( $_REQUEST ['act'] ) ? trim ( $_REQUEST ['act'] ) : 'default';

$affiliate = unserialize ( $GLOBALS ['_CFG'] ['affiliate'] );
$smarty->assign ( 'affiliate', $affiliate );
$back_act = '';

// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr = array ('login', 'act_login', 'register', 'act_register', 'modify', 'act_edit_password', 'get_password', 'send_pwd_email', 'password', 'signin', 'add_tag', 'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_registered', 'check_email', 'clear_history', 'qpassword_name', 'get_passwd_question', 'check_answer' );

/* 显示页面的action列表 */
$ui_arr = array ('register', 'login', 'profile', 'order_list', 'order_detail', 'address_list', 'collection_list', 'message_list', 'tag_list', 'get_password', 'reset_password', 'booking_list', 'add_booking', 'account_raply', 'account_deposit', 'account_log', 'account_detail', 'act_account', 'pay', 'default', 'bonus', 'group_buy', 'group_buy_detail', 'affiliate', 'comment_list', 'validate_email', 'track_packages', 'transform_points', 'qpassword_name', 'get_passwd_question', 'check_answer' );

/* 未登录处理 */
if (empty ( $_SESSION ['user_id'] )) {
	if (! in_array ( $action, $not_login_arr )) {
		if (in_array ( $action, $ui_arr )) {
			/* 如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
            if ($action == 'login')
            {
                if (isset($_REQUEST['back_act']))
                {
                    $back_act = trim($_REQUEST['back_act']);
                }
            }
            else
            {}*/
			if (! empty ( $_SERVER ['QUERY_STRING'] )) {
				$back_act = 'user.php?' . strip_tags ( $_SERVER ['QUERY_STRING'] );
			}
			$action = 'login';
		} else {
			//未登录提交数据。非正常途径提交数据！
			die ( $_LANG ['require_login'] );
		}
	}
}

/* 如果是显示页面，对页面进行相应赋值 */
if (in_array ( $action, $ui_arr )) {
	assign_template ();
	$position = assign_ur_here ( 0, $_LANG ['user_center'] );
	$smarty->assign ( 'page_title', $position ['title'] ); // 页面标题
	$smarty->assign ( 'ur_here', $position ['ur_here'] );
	$sql = "SELECT value FROM " . $ecs->table ( 'shop_config' ) . " WHERE id = 419";
	$row = $db->getRow ( $sql );
	$car_off = $row ['value'];
	$smarty->assign ( 'car_off', $car_off );
	/* 是否显示积分兑换 */
	if (! empty ( $_CFG ['points_rule'] ) && unserialize ( $_CFG ['points_rule'] )) {
		$smarty->assign ( 'show_transform_points', 1 );
	}
	$smarty->assign ( 'helps', get_shop_help () ); // 网店帮助
	$smarty->assign ( 'data_dir', DATA_DIR ); // 数据目录
	$smarty->assign ( 'action', $action );
	$smarty->assign ( 'lang', $_LANG );
}

//用户中心欢迎页
if ($action == 'default') {
	include_once (ROOT_PATH . 'includes/lib_clips.php');
	if ($rank = get_rank_info ()) {
		$smarty->assign ( 'rank_name', sprintf ( $_LANG ['your_level'], $rank ['rank_name'] ) );
		if (! empty ( $rank ['next_rank_name'] )) {
			$smarty->assign ( 'next_rank_name', sprintf ( $_LANG ['next_level'], $rank ['next_rank'], $rank ['next_rank_name'] ) );
		}
	}
	$smarty->assign ( 'info', get_user_default ( $user_id ) );
	$smarty->assign ( 'user_notice', $_CFG ['user_notice'] );
	$smarty->assign ( 'prompt', get_user_prompt ( $user_id ) );
	$smarty->display ( 'user_clips.html' );
}

/* 显示会员注册界面 */
if ($action == 'register') {
	$smarty->display ( 'add_user.html' );
} 

/* 注册会员的处理 */
elseif ($action == 'act_register') {
	/* 增加是否关闭注册 */
	if ($_CFG ['shop_reg_closed']) {
		$smarty->assign ( 'action', 'register' );
		$smarty->assign ( 'shop_reg_closed', $_CFG ['shop_reg_closed'] );
		$smarty->display ( 'user_passport.html' );
	} else {
		$card_number = isset ( $_POST ['card_id'] ) ? addslashes(trim ( $_POST ['card_id'] )) : '';
		if(empty($card_number)){
			show_msg ( "请刷本店的会员卡!" , "返回注册", "user_with_card.php");
		}else{
			$card_number = substr($card_number, 0, 12);
		}
		$sql = "select card_id, store_id from " . $GLOBALS ['ecs']->table ( 'user_card' ) . " where card_number='$card_number' and card_state=0 ";
		$card = $db->getRow ( $sql );
		if (empty ( $card ) || $card==FALSE ) {
			show_msg ( "请使用没激活的会员卡!" , "返回购物", "shop_specials.php");
		}
		if(isset($_SESSION['store']['store_id'])&&$_SESSION['store']['store_id'] !=0){
// 			$sql="update ". $ecs->table('user_card') . " set store_id=".$_SESSION['store']['store_id']." where card_id='".$card['card_id']."'";
// 			$db->query($sql);
		}else {
			show_msg('请店家预先登录！','前往登录界面','shop_store.php?act=login');
		}
		$other ['real_name'] = isset ( $_POST ['name'] ) ? addslashes( trim ( $_POST ['name'] ) ) : '';
		if (empty ( $other ['real_name'] ) || strlen ( $other ['real_name'] ) < 2) {
			show_msg ( "请填写姓名!" , "请重新填写", "user_with_card.php?act=register");
		}
		if (! is_numeric ( $_POST ['sex'] ) || strpos ( $_POST ['sex'], "." ) != false) {
			show_msg ( "请选择性别!" , "请重新填写", "user_with_card.php?act=register");
		} else {
			$other ['sex'] = intval ( $_POST ['sex'] );
			$other ['sex'] = in_array ( $other ['sex'], array (0, 1, 2 ) ) ? $other ['sex'] : 0;
		}
		if (! is_numeric ( $_POST ['r_style'] ) || strpos ( $_POST ['r_style'], "." ) != false) {
			show_msg ( "请选择注册类型!" , "请重新填写", "user_with_card.php?act=register");
		} else {
			$other ['r_style'] = intval ( $_POST ['r_style'] );
			$other ['r_style'] = in_array ( $other ['r_style'], array (0, 1, 2 ) ) ? $other ['r_style'] : 0;
		}
		
		$other ['mobile_phone'] = isset ( $_POST ['phone'] ) ? addslashes ( trim ( $_POST ['phone'] ) ) : '';
		//if (strlen ( $other ['mobile_phone'] ) != 8&&strlen ( $other ['mobile_phone'] ) != 11) {
		$reg = "/(^((0[1,2]{1}\d{1}-?\d{8})|(0[3-9]{1}\d{2}-?\d{7,8}))$)|(^0?(13[0-9]|15[0-9]|18[0-9]|14[0-9])[0-9]{8}$)/";
		if(!preg_match($reg, $other ['mobile_phone'])){
			show_msg ( "请填写正确的手机号码!" , "请重新填写", "user_with_card.php?act=register");
		}
		
		$other ['password'] = isset ( $_POST ['pass'] ) ? md5(addslashes ( trim ( $_POST ['pass'] ) )) : '';
		if (strlen ( $other ['password'] ) < 6&&strlen ( $other ['password'] ) > 11) {
			show_msg ( "请填写正确的密码!" , "请重新填写", "user_with_card.php?act=register");
		}			
		
		/*注册时间*/
		$other['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));		
		$ip = real_ip();
		$time_now = time();
		
		if($other ['r_style']=="1"){
			$user_id = intval($db->getOne('SELECT user_id FROM '.$ecs->table('users')." where user_name = '".$other ['mobile_phone']."' and password = '".$other ['password']."'"));
			if($user_id==0){
				$new_user = array(
						'store_id'      => $_SESSION['store']['store_id'],
						'user_name'     => $other ['mobile_phone'],
						'mobile_phone'  => $other ['mobile_phone'],
						'password'      => $other ['password'],
						'reg_time'   	=> $time_now,
						'last_login'    => $time_now,
						'last_ip'       => $ip,
						'visit_count'   => 1
				);
				$db->autoExecute($ecs->table('users'), $new_user, 'INSERT');
				$user_id = $db->insert_id();
			}else {
				show_msg ( "手机号码已注册!" , "请重新填写", "user_with_card.php?act=register");
			}
			$db->autoExecute ( $ecs->table ( 'users' ), $other, 'UPDATE', "user_id = '$user_id'" );
			$sql = "update " . $GLOBALS ['ecs']->table ( 'user_card' ) . " set user_id='$user_id',card_state=1,reg_time='$time_now'" . " store_id=".$_SESSION['store']['store_id']." where card_number='$card_number'";
			$db->query ( $sql );
			
			$sql = "SELECT country,province,city,district FROM " . $GLOBALS['ecs']->table('store') . " WHERE store_id =".$_SESSION['store']['store_id'];
			$rows = $GLOBALS['db']->getRow($sql);
			
			$sql = "INSERT INTO " .$GLOBALS['ecs']->table('user_address'). " (store_id,user_id,consignee,mobile,country,province,city,district)" .
					"VALUES (".$_SESSION['store']['store_id'].", '$user_id', '".$other ['real_name']."','".$other ['mobile_phone']."','$rows[country]','$rows[province]','$rows[city]','$rows[district]')";
			$GLOBALS['db']->query($sql);
			
			show_msg ( sprintf ( '会员卡 : %s 姓名:%s 注册成功', $card_number, $other ['real_name'] ), "返回购物", "shop_specials.php");
		}else if($other ['r_style']=="0"){
			$user_id = intval($db->getOne('SELECT user_id FROM '.$ecs->table('users')." where user_name = '".$other ['mobile_phone']."' and password = '".$other ['password']."'"));
			if($user_id!=0){
				$sql = "delete from " . $GLOBALS ['ecs']->table ( 'user_card' ) . " where card_number='$card_number'";
				$db->query ( $sql );
				$sql = "update " . $GLOBALS ['ecs']->table ( 'user_card' ) . " set card_number='$card_number'" . " where user_id='$user_id'";
				$db->query ( $sql );
				show_msg ( sprintf ( '会员卡 : %s 姓名:%s 激活成功', $card_number, $other ['real_name'] ), "返回购物", "shop_specials.php");
			}else {
				show_msg ( "该用户不能激活!" , "请重新填写", "user_with_card.php?act=register");
			}
			
		}

		/* 提示信息 */
// 		show_msg ( sprintf ( '会员卡 : %s 姓名:%s 注册成功', $card_number, $other ['real_name'] ), "返回购物", "shop_specials.php");
	}
}

?>