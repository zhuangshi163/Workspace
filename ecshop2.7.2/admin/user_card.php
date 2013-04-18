<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: users.php 17063 2010-03-25 06:35:46Z liuhui $
 */

define ( 'IN_ECS', true );

require (dirname ( __FILE__ ) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 会员卡列表
/*------------------------------------------------------ */
if($_REQUEST ['act'] == 'check_state'){
	$card_number = empty ( $_POST ['card_number'] )? "":trim($_POST ['card_number']);
	if(empty($card_number)){
		
	}else{
		$sql = "select card_id from ". $GLOBALS['ecs']->table('user_card') . "WHERE card_num='".$card_number."' AND card_state=0";
		$card['card_id'] = $GLOBALS ['db']->getOne ( $sql );
		if(!empty($card['card_id'])){
			$card ['reg_time'] = local_strtotime ( "now" );
		}
	}
}
elseif ($_REQUEST ['act'] == 'list') {
	/* 检查权限 */
	admin_priv ( 'users_manage' );
	//    $sql = "SELECT card_id, card_number, store_id, user_id, card_state, reg_time FROM ".$ecs->table('user_card')." ORDER BY reg_time DESC ";
	//    $rs = $db->query($sql);
	header ( "Content-type: text/html; charset=gbk2312" );
	$store_list = get_store_list ();
	$smarty->assign ( 'store_list', $store_list );
	
	$card_list = user_card_list ();
	$smarty->assign ( 'card_list', $card_list ['card_list'] );
	
	$smarty->assign ( 'ur_here', $_LANG ['002_cards_list'] );
	$smarty->assign ( 'action_link', array ('text' => $_LANG ['001_cards_add'], 'href' => 'user_card.php?act=add' ) );
	$smarty->assign ( 'filter', $card_list ['filter'] );
	$smarty->assign ( 'record_count', $card_list ['record_count'] );
	$smarty->assign ( 'page_count', $card_list ['page_count'] );
	$smarty->assign ( 'full_page', 1 );
	//    $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');
	

	assign_query_info ();
	$smarty->display ( 'user_card_list.htm' );
} /*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST ['act'] == 'query') {
	admin_priv ( 'users_manage' );
	
	$card_list = user_card_list ();
	
	$smarty->assign ( 'card_list', $card_list ['card_list'] );
	$smarty->assign ( 'filter', $card_list ['filter'] );
	$smarty->assign ( 'record_count', $card_list ['record_count'] );
	$smarty->assign ( 'page_count', $card_list ['page_count'] );
	
	//    $sort_flag  = sort_flag($user_list['filter']);
	//    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	

	make_json_result ( $smarty->fetch ( 'user_card_list.htm' ), '', array ('filter' => $card_list ['filter'], 'page_count' => $card_list ['page_count'] ) );
} /*------------------------------------------------------ */
//-- 批量添加会员卡
/*------------------------------------------------------ */
elseif ($_REQUEST ['act'] == 'add') {
	/* 检查权限 */
	admin_priv ( 'users_manage' );
	
	$store_list = get_store_list ();
	$smarty->assign ( 'store_list', $store_list );
	$smarty->assign ( 'action_link', array ('text' => $_LANG ['002_cards_list'], 'href' => 'user_card.php?act=list' ) );
	$smarty->assign ( 'form_action', 'insert' );
	assign_query_info ();
	$smarty->display ( 'user_card_info.htm' );
} /*------------------------------------------------------ */
//-- 修改会员卡信息
/*------------------------------------------------------ */
elseif ($_REQUEST ['act'] == 'edit') {
	/* 检查权限 */
	admin_priv ( 'users_manage' );
	$card = get_cards ( "c.card_id", $_REQUEST ['card_id'] );
	$smarty->assign ( 'card', $card );
	
	$store_list = get_store_list ();
	$smarty->assign ( 'store_list', $store_list );
	$smarty->assign ( 'action_link', array ('text' => $_LANG ['002_cards_list'], 'href' => 'user_card.php?act=list' ) );
	$smarty->assign ( 'form_action', 'update' );
	assign_query_info ();
	$smarty->display ( 'user_card_info.htm' );
} elseif ($_REQUEST ['act'] == 'freeze') {
	/* 检查权限 */
	if (update_cards ( $_GET ['card_id'], "card_state", 2 )) {
		$sql = "SELECT card_number FROM " . $ecs->table ( 'user_card' ) . " WHERE card_id = '" . $_GET ['card_id'] . "'";
		$username = $db->getOne ( $sql );
		/* 记录管理员操作 */
		admin_log ( addslashes ( $username ), 'freeze', 'user_card' );
		
		/* 提示信息 */
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'user_card.php?act=list' );
		sys_msg ( sprintf ( $_LANG ['oper_success'], $username ), 0, $link );
	
	} else {
		/* 提示信息 */
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'user_card.php?act=list' );
		sys_msg ( $_LANG ['oper_failure'], 0, $link );
	}

} 

elseif ($_REQUEST ['act'] == 'active') {
	/* 检查权限 */
	admin_priv ( 'users_manage' );
	if (update_cards ( $_GET ['card_id'], "card_state", 1 )) {
		$sql = "SELECT card_number FROM " . $ecs->table ( 'user_card' ) . " WHERE card_id = '" . $_GET ['card_id'] . "'";
		$username = $db->getOne ( $sql );
		/* 记录管理员操作 */
		admin_log ( addslashes ( $username ), 'freeze', 'user_card' );
		
		/* 提示信息 */
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'user_card.php?act=list' );
		sys_msg ( sprintf ( $_LANG ['oper_success'], $username ), 0, $link );
	
	} else {
		/* 提示信息 */
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'user_card.php?act=list' );
		sys_msg ( $_LANG ['oper_failure'], 0, $link );
	}

} 
elseif ($_REQUEST ['act'] == 'update') {
	/* 检查权限 */
	admin_priv ( 'users_manage' );
	$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'user_card' ) . 
		" SET store_id = '".$_POST['store_id']."',card_state='".$_POST['card_state']."'" . 
		" WHERE card_id='".$_POST['card_id']."'";
	$GLOBALS ['db']->query ( $sql );
		$sql = "SELECT card_number FROM " . $ecs->table ( 'user_card' ) . " WHERE card_id = '" . $_POST ['card_id'] . "'";
		$card_number = $db->getOne ( $sql );
		/* 记录管理员操作 */
		admin_log ( addslashes ( $card_number ), 'freeze', $card_number );
		
		/* 提示信息 */
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'user_card.php?act=list' );
		sys_msg ( sprintf ( $_LANG ['oper_success'], $card_number ), 0, $link );
	
		/* 提示信息 */
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'user_card.php?act=list' );
		sys_msg ( $_LANG ['oper_failure'], 0, $link );

}
/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST ['act'] == 'insert') {
	date_default_timezone_set('PRC'); //设置中国时区
	/* 检查权限 */
	admin_priv ( 'users_manage' );
	$count = empty ( $_POST ['count'] ) ? '' : trim ( $_POST ['count'] );
	if (! is_numeric ( $count ) || strpos ( $count, "." ) !== false) {
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'javascript:history.back(-1)' );
		sys_msg ( $_LANG ['count_isnot_num'], 0, $link );
	}
	$store_id = empty ( $_POST ['store_id'] ) ? '' : trim ( $_POST ['store_id'] );
		
	if (!empty($store_id)&&(! is_numeric ( $store_id ) || strpos ( $store_id, "." ) !== false)) {
		$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'javascript:history.back(-1)' );
		sys_msg ( $_LANG ['storeId_isnot_num'], 0, $link );
	}
	$card ['store_id'] = $store_id;
//	$card ['reg_time'] = local_strtotime ( "now" );
	mt_srand ( ( double ) microtime () * 100000 );
	$card_numbers = array ();
	for($i = 0; $i < $count; $i ++) {
		$error_no = 0;
		do {
			$card ['card_number'] = date ( 'ymd' ) . str_pad ( mt_rand ( 1, 999999 ), 6, '0', STR_PAD_LEFT );
			
			$GLOBALS ['db']->autoExecute ( $GLOBALS ['ecs']->table ( 'user_card' ), $card, 'INSERT' );
			$error_no = $GLOBALS ['db']->errno ();
			
			if ($error_no > 0 && $error_no != 1062) {
				die ( $GLOBALS ['db']->errorMsg () );
			}
			$card_numbers [] = $card ['card_number'];
		} while ( $error_no == 1062 ); //如果是会员卡号重复则重新提交数据
	}
	$filename = ecs_iconv ( EC_CHARSET, "GB2312", "会员卡报表（".date('Y-m-d')."）");
	//header ( "content=text/html; charset=gb2312");
	//header ( "Content-type: application/vnd.ms-excel;");
	header ( "Content-type: application/vnd.ms-excel;" );
	header ( "Content-Disposition: attachment; filename=$filename.xls");
	
	$card_list = get_cards ( "c.card_number", $card_numbers );
	$GLOBALS['smarty']->assign("card_list", $card_list);
	$GLOBALS['smarty']->assign("type", "generate_card");
	$table = $GLOBALS['smarty']->fetch('excel_user_card.htm');
	//$table = ecs_iconv ( EC_CHARSET, 'GB2312', $table );
	echo $table;
	exit ();
} elseif ($_REQUEST ['act'] == 'batch') {
	admin_priv ( 'users_manage' );
	$card_ids = ! empty ( $_POST ['checkboxes'] ) ? join ( ',', $_POST ['checkboxes'] ) : 0;
	$card_ids = explode(",", $card_ids);
	if (isset ( $_POST ['type'] )) {
		/* 放入回收站 */
		if ($_POST ['type'] == 'store_move_to') {
			
			update_cards ( $card_ids, "store_id", $_POST ['store_id'] );
			/* 记录日志 */
			admin_log ( '', 'cards_move_to', 'store' );
		}elseif ($_POST ['type'] == 'freeze'){
			update_cards ( $card_ids, "card_state", 2);
			admin_log ( '', 'cards_freeze', 'freeze' );
		}elseif ($_POST ['type'] == 'active'){
			update_cards ( $card_ids, "card_state", 1);
			admin_log ( '', 'cards_active', 'active' );
		}elseif ($_POST ['type'] == 'export_excel') {
			header ( "Content-type: application/vnd.ms-excel;" );
			header ( "Content-Disposition: attachment; filename=test.xls" );
			
			$card_list = get_cards ( "c.card_id", $card_ids );
			$GLOBALS['smarty']->assign("type", "card_list");
			$GLOBALS['smarty']->assign("card_list", $card_list);
			$table = $GLOBALS['smarty']->fetch('excel_user_card.htm');
			//$table = ecs_iconv ( EC_CHARSET, 'GB2312', $table );
			echo $table;
			exit ();
		}
	}
	$link [] = array ('text' => $_LANG ['go_back'], 'href' => 'javascript:history.back(-1)' );
	sys_msg ( $_LANG ['oper_success'], 0, $link);
}
/**
 * 返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function user_card_list() {
	$result = get_filter ();
	if ($result === false) {
		$filter['state'] = isset ( $_REQUEST ['state'] ) ? intval($_REQUEST ['state']) :-1;
		$filter ['store_id'] = empty ( $_REQUEST ['store_id'] ) ? '' : $_REQUEST ['store_id'];
		$filter ['keyword'] = empty ( $_REQUEST ['keyword'] ) ? '' : trim ( $_REQUEST ['keyword'] );
		if (isset ( $_REQUEST ['is_ajax'] ) && $_REQUEST ['is_ajax'] == 1) {
			$filter ['keyword'] = json_str_iconv ( $filter ['keyword'] );
		}
		
		$where = " WHERE 1 ";
		if ($filter ['store_id']) {
			$where .= " AND c.store_id='" . $filter ['store_id'] . "' ";
		}
		if($filter['state']!=-1){
			$where .= " AND c.card_state='" . $filter ['state'] . "' ";
		}
		if (! empty ( $filter ['keyword'] )) {
			//$filter ['keyword'] = ecs_iconv ( EC_CHARSET, 'GB2312', $filter ['keyword'] );
			$where .= " AND (s.store_remark LIKE '%" . mysql_like_quote ( $filter ['keyword'] ) . "%' OR c.card_number LIKE '%" . mysql_like_quote ( $filter ['keyword'] ) . "%')";
		}
		$filter ['sort_by'] = empty ( $_REQUEST ['sort_by'] ) ? 'card_id' : trim ( $_REQUEST ['sort_by'] );
		$filter ['sort_order'] = empty ( $_REQUEST ['sort_order'] ) ? 'ASC' : trim ( $_REQUEST ['sort_order'] );
		
		/* 分页大小 */
		$sql = "SELECT count(*) FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " . " left outer join " . $GLOBALS ['ecs']->table ( 'store' ) . " as s on c.store_id=s.store_id " . $where;
		$filter ['record_count'] = $GLOBALS ['db']->getOne ( $sql );
		
		$filter = page_and_size ( $filter );
		
		/* 查询 */
		$sql = "SELECT c.card_id, c.card_number, c.store_id, s.store_remark, u.user_name, u.real_name, c.card_state " . " FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " . 
			" left outer join " . $GLOBALS ['ecs']->table ( 'store' ) . " as s on c.store_id=s.store_id ".
			" left outer join " . $GLOBALS ['ecs']->table ( 'users' ) . " as u on c.user_id=u.user_id "
				 . $where . " ORDER by " . $filter ['sort_by'] . ' ' . $filter ['sort_order'] . " LIMIT " . $filter ['start'] . ',' . $filter ['page_size'];
		
		set_filter ( $filter, $sql );
	} else {
		$sql = $result ['sql'];
		$filter = $result ['filter'];
	}
	
	$card_list = $GLOBALS ['db']->getAll ( $sql );
	$filter ['keyword'] = stripslashes ( $filter ['keyword'] );
	$arr = array ('card_list' => $card_list, 'filter' => $filter, 'page_count' => $filter ['page_count'], 'record_count' => $filter ['record_count'] );
	
	return $arr;
}
/**
 * 修改会员卡中某字段值
 * @param   string  $goods_id   会员卡编号，可以为多个，用 ',' 隔开
 * @param   string  $field      字段名
 * @param   string  $value      字段值
 * @return  bool
 */
function update_cards($card_id, $field, $value) {
	if (is_array ( $card_id )) {
		/* 清除缓存 */
		clear_cache_files ();
		
		$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'user_card' ) . " SET $field = '$value' " . "WHERE card_id " . db_create_in ( $card_id );
		return $GLOBALS ['db']->query ( $sql );
	} elseif ($card_id) {
		/* 清除缓存 */
		clear_cache_files ();
		
		$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'user_card' ) . " SET $field = '$value' " . "WHERE card_id='$card_id'";
		return $GLOBALS ['db']->query ( $sql );
	} else {
		return false;
	}
}

function get_cards($field, $value) {
	if (is_array ( $value )) {
		$sql = "SELECT c.card_id, c.card_number, c.store_id, c.card_state, s.store_remark, u.user_name, u.real_name" . " FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " . 
			" left outer join " . $GLOBALS ['ecs']->table ( 'store' ) . " as s on c.store_id=s.store_id " . 
			" left outer join " . $GLOBALS ['ecs']->table ( 'users' ) . " as u on c.user_id=u.user_id ".
				"WHERE $field " . db_create_in ( $value );
		return $GLOBALS ['db']->getAll ( $sql );
	} else {
		$sql = "SELECT c.card_id, c.card_number, c.store_id, c.card_state, s.store_remark, u.user_name, u.real_name" . " FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " . 
			" left outer join " . $GLOBALS ['ecs']->table ( 'store' ) . " as s on c.store_id=s.store_id " . 
			" left outer join " . $GLOBALS ['ecs']->table ( 'users' ) . " as u on c.user_id=u.user_id ".
				"WHERE $field='$value'";
		return $GLOBALS ['db']->getRow ( $sql );
	}

}

/**
 * 取得门店列表
 * @return array 门店列表 id => name
 */
function get_store_list()
{
	$sql = 'SELECT store_id, store_remark FROM ' . $GLOBALS['ecs']->table('store') . " ORDER BY store_id DESC ";
	$res = $GLOBALS['db']->getAll($sql);

	$store_list = array();
	foreach ($res AS $row)
	{
		$store_list[$row['store_id']] = addslashes($row['store_remark']);
	}

	return $store_list;
}
/**
 * 取得门店名称
 * @return string 门店名称 store_remark
 */
function get_store_name($store_id)
{
	$sql = 'SELECT store_remark FROM ' . $GLOBALS['ecs']->table('store') . " WHERE store_id='".$store_id."'";
	$res = $GLOBALS['db']->getOne($sql);

	return ecs_iconv(EC_CHARSET, 'GB2312', $res);
}
?>