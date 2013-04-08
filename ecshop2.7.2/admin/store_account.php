<?php

/**
 * ECSHOP 店家帐目管理(包括充值)
 * ============================================================================
 * 版权所有 2005-2013 广东健康盾防伪有限公司，并保留所有权利。
 * 网站地址: http://www.jkdun.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: store_account.php 17063 2013-01-04 15:03:46Z liuhui $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
   // $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 店家余额记录列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('store_recharge');

    /* 指定店家的ID为查询条件 */
    $store_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    //	判断是否为店家管理员
	$is_store_admin = !empty($_SESSION['store_id']) ? 1 : 0;
	$smarty->assign('is_store_admin',    $is_store_admin);
	/* 支付方式的ID */
	$pay_name = $_LANG[alipay];
	$sql = 'SELECT pay_id FROM ' .$GLOBALS['ecs']->table('payment').
		" WHERE pay_name = '$pay_name' AND enabled = 1";
	$pid = $GLOBALS['db']->getOne($sql);
	$smarty->assign('is_store_admin',    $is_store_admin);
	$smarty->assign('pid',    $pid);
	$store_account_money=get_store_surplus($_SESSION['store_id']);
	$smarty->assign('store_account_money',   $store_account_money);
	
    /* 获得支付方式列表 */
    $payment = array();
    $sql = "SELECT pay_id, pay_name FROM ".$ecs->table('payment').
           " WHERE enabled = 1 AND pay_code NOT IN ('cod', 'balance') ORDER BY pay_id";
    $res = $db->query($sql);

    while ($row = $db->fetchRow($res))
    {
        $payment[$row['pay_name']] = $row['pay_name'];
    }

    /* 模板赋值 */
    if (isset($_REQUEST['process_type']))
    {
        $smarty->assign('process_type_' . intval($_REQUEST['process_type']), 'selected="selected"');
    }
    if (isset($_REQUEST['is_paid']))
    {
        $smarty->assign('is_paid_' . intval($_REQUEST['is_paid']), 'selected="selected"');
    }
    $smarty->assign('ur_here',       $_LANG['store_rechange_list']);
    $smarty->assign('id',            $store_id);
    $smarty->assign('payment_list',  $payment);
    //如果是店家管理员，显示添加申请
    if($is_store_admin){
    	$smarty->assign('action_link',   array('text' => $_LANG['surplus_add'], 'href'=>'store_account.php?act=add'));
    }

    $list = account_list();
    
    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    $smarty->assign('full_page',    1);
    
    assign_query_info();
    $smarty->display('store_account_list.htm');
}

/*------------------------------------------------------ */
//-- 添加/编辑店家余额页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    admin_priv('store_recharge'); //权限判断

    $ur_here  = ($_REQUEST['act'] == 'add') ? $_LANG['surplus_add'] : $_LANG['surplus_edit'];
    $form_act = ($_REQUEST['act'] == 'add') ? 'insert' : 'update';
    $id       = isset($_GET['id']) ? intval($_GET['id']) : 0;

    /* 获得支付方式列表, 不包括“货到付款” */
    $store_account = array();
    $payment = array();
    $sql = "SELECT pay_id, pay_name FROM ".$ecs->table('payment').
           " WHERE enabled = 1 AND pay_code NOT IN ('cod', 'balance') ORDER BY pay_id";
    $res = $db->query($sql);

    while ($row = $db->fetchRow($res))
    {
        $payment[$row['pay_name']] = $row['pay_name'];
    }

    if ($_REQUEST['act'] == 'edit')
    {
        /* 取得余额信息 */
        $store_account = $db->getRow("SELECT * FROM " .$ecs->table('store_account') . " WHERE id = '$id'");

        // 如果是负数，去掉前面的符号
        $store_account['amount'] = str_replace('-', '', $store_account['amount']);

        /* 取得店家名称 */
        $sql = "SELECT store_name FROM " .$ecs->table('store'). " WHERE store_id = '$store_account[store_id]'";
        $store_name = $db->getOne($sql);
    }
    else
    {
        $surplus_type = '';
        $store_name    = '';
        $store_id = $_SESSION['store_id'];
        $sql = "SELECT store_name from ".$ecs->table('store')." where store_id=".$store_id;
        $store_name = $db->getOne($sql);
    }

    /* 模板赋值 */
    $is_store_admin = !empty($_SESSION['store_id']) ? 1 : 0;
    $smarty->assign('is_store_admin',    $is_store_admin);
    $smarty->assign('ur_here',          $ur_here);
    $smarty->assign('form_act',         $form_act);
    $smarty->assign('payment_list',     $payment);
    $smarty->assign('action',           $_REQUEST['act']);
    $smarty->assign('user_surplus',     $store_account);
    $smarty->assign('store_name',        $store_name);
    if ($_REQUEST['act'] == 'add')
    {
        $href = 'store_account.php?act=list';
    }
    else
    {
        $href = 'store_account.php?act=list&' . list_link_postfix();
    }
    $smarty->assign('action_link', array('href' => $href, 'text' => $_LANG['store_rechange_list']));

    assign_query_info();
    $smarty->display('store_account_info.htm');
}

/*------------------------------------------------------ */
//-- 添加/编辑会员余额的处理部分
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 权限判断 */
    admin_priv('store_recharge');

    /* 初始化变量 */
    $id           = isset($_POST['id'])            ? intval($_POST['id'])             : 0;
    $is_paid      = !empty($_POST['is_paid'])      ? intval($_POST['is_paid'])        : 0;
    $amount       = !empty($_POST['amount'])       ? floatval($_POST['amount'])       : 0;
    $process_type = !empty($_POST['process_type']) ? intval($_POST['process_type'])   : 0;
    $store_name    = !empty($_POST['store_id'])      ? trim($_POST['store_id'])       : '';
    $admin_note   = !empty($_POST['admin_note'])   ? trim($_POST['admin_note'])       : '';
    $store_note    = !empty($_POST['store_note'])    ? trim($_POST['store_note'])        : '';
    $payment      = !empty($_POST['payment'])      ? trim($_POST['payment'])          : '';

    $store_id = $db->getOne("SELECT store_id FROM " .$ecs->table('store'). " WHERE store_name = '$store_name'");

    /* 此店家是否存在 */
    if ($store_id == 0)
    {
        $link[] = array('text' => $_LANG['go_back'], 'href'=>'javascript:history.back(-1)');
        sys_msg($_LANG['storename_not_exist'], 0, $link);
    }

    /* 退款，检查余额是否足够 */
    if ($process_type == 1)
    {
        $store_account = get_store_surplus($store_id);

        /* 如果扣除的余额多于此会员拥有的余额，提示 */
        if ($amount > $store_account)
        {
            $link[] = array('text' => $_LANG['go_back'], 'href'=>'javascript:history.back(-1)');
            sys_msg($_LANG['surplus_amount_error'], 0, $link);
        }
    }

    if ($_REQUEST['act'] == 'insert')
    {
        /* 入库的操作 */
        if ($process_type == 1)
        {
            $amount = (-1) * $amount;
        }
        $sql = "INSERT INTO " .$ecs->table('store_account').
               " VALUES ('', '$store_id', '$_SESSION[admin_name]', '$amount', '".gmtime()."', '".gmtime()."', '$admin_note', '$store_note', '$process_type', '$payment', '$is_paid')";
        $db->query($sql);
        $id = $db->insert_id();
    }
    else
    {
        /* 更新数据表 */
        $sql = "UPDATE " .$ecs->table('store_account'). " SET ".
               "admin_note   = '$admin_note', ".
               "store_note    = '$store_note' ".
               //"payment      = '$payment' ".
              "WHERE id      = '$id'";
        $db->query($sql);
    }

    /*
    // 更新店家余额数量
    if ($is_paid == 1)
    {
        $change_desc = $amount > 0 ? $_LANG['surplus_type_0'] : $_LANG['surplus_type_1'];
        $change_type = $amount > 0 ? ACT_SAVING : ACT_DRAWING;
        log_store_account_change($store_id, $amount, 0, 0, 0, $change_desc, $change_type);
    }
    */

    //如果是预付款并且未确认，向pay_log插入一条记录
    if ($_REQUEST['act'] == 'insert' && $process_type == 0 && $is_paid == 0 && $payment =='支付宝')
    {

        /* 支付方式的ID */
        $sql = 'SELECT pay_id FROM ' .$GLOBALS['ecs']->table('payment').
        " WHERE pay_name = '$payment' AND enabled = 1";
        $surplus['payment_id'] = $GLOBALS['db']->getOne($sql);
        
        if ($surplus['payment_id'] <= 0)
        {
        	show_message($_LANG['select_payment_pls']);
        }
        include_once(ROOT_PATH . 'includes/lib_clips.php');
        include_once(ROOT_PATH .'includes/lib_payment.php');
        include_once(ROOT_PATH . 'includes/lib_order.php');
        //获取支付方式名称
        $payment_info = array();
        $payment_info = payment_info($surplus['payment_id']);
        $surplus['payment'] = $payment_info['pay_name'];
        $surplus['rec_id'] = $id;

        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);
        
        //生成伪订单号, 不足的时候补0
        $order = array();
        $order['order_sn']       = $surplus['rec_id'];
        $order['user_name']      = $_SESSION['admin_name'];
        $order['surplus_amount'] = $amount;
        
        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);
        
        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $amount + $payment_info['pay_fee'];
        
        //记录支付log
        $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_STORE_SURPLUS, 0);
        
        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');
        
        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
        
        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($amount, false));
        $smarty->assign('order',   $order);
       	$smarty->assign('action',  'act_account');
		$smarty->display('store_transaction.htm');
		exit();
    }

    /* 记录管理员操作 */
    if ($_REQUEST['act'] == 'update')
    {
        admin_log($store_name, 'edit', 'store_surplus');
    }
    else
    {
        admin_log($store_name, 'add', 'store_surplus');
    }

    /* 提示信息 */
    if ($_REQUEST['act'] == 'insert')
    {
        $href = 'store_account.php?act=list';
    }
    else
    {
        $href = 'store_account.php?act=list&' . list_link_postfix();
    }
    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = $href;

    $link[1]['text'] = $_LANG['continue_add'];
    $link[1]['href'] = 'store_account.php?act=add';

    sys_msg($_LANG['attradd_succed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 审核会员余额页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'check')
{
    /* 检查权限 */
    admin_priv('store_recharge');

    /* 初始化 */
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    /* 如果参数不合法，返回 */
    if ($id == 0)
    {
        ecs_header("Location: store_account.php?act=list\n");
        exit;
    }

    /* 查询当前的预付款信息 */
    $account = array();
    $account = $db->getRow("SELECT * FROM " .$ecs->table('store_account'). " WHERE id = '$id'");
    $account['add_time'] = local_date($_CFG['time_format'], $account['add_time']);

    //余额类型:预付款，退款申请，购买商品，取消订单
    if ($account['process_type'] == 0)
    {
        $process_type = $_LANG['surplus_type_0'];
    }
    elseif ($account['process_type'] == 1)
    {
        $process_type = $_LANG['surplus_type_1'];
    }
    elseif ($account['process_type'] == 2)
    {
        $process_type = $_LANG['surplus_type_2'];
    }
    else
    {
        $process_type = $_LANG['surplus_type_3'];
    }

    $sql = "SELECT store_name FROM " .$ecs->table('store'). " WHERE store_id = '$account[store_id]'";
    $store_name = $db->getOne($sql);

    /* 模板赋值 */
    $smarty->assign('ur_here',      $_LANG['check']);
    $account['user_note'] = htmlspecialchars($account['user_note']);
    $smarty->assign('surplus',      $account);
    $smarty->assign('process_type', $process_type);
    $smarty->assign('store_name',    $store_name);
    $smarty->assign('id',           $id);
    $smarty->assign('action_link',  array('text' => $_LANG['store_rechange_list'],
    'href'=>'store_account.php?act=list&' . list_link_postfix()));

    /* 页面显示 */
    assign_query_info();
    $smarty->display('store_account_check.htm');
}

/*------------------------------------------------------ */
//-- 更新会员余额的状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'action')
{
    /* 检查权限 */
    admin_priv('store_recharge');

    /* 初始化 */
    $id         = isset($_POST['id'])         ? intval($_POST['id'])             : 0;
    $is_paid    = isset($_POST['is_paid'])    ? intval($_POST['is_paid'])        : 0;
    $admin_note = isset($_POST['admin_note']) ? trim($_POST['admin_note'])       : '';

    /* 如果参数不合法，返回 */
    if ($id == 0 || empty($admin_note))
    {
        ecs_header("Location: user_account.php?act=list\n");
        exit;
    }

    /* 查询当前的预付款信息 */
    $account = array();
    $account = $db->getRow("SELECT * FROM " .$ecs->table('store_account'). " WHERE id = '$id'");
    $amount  = $account['amount'];

    //如果状态为未确认
    if ($account['is_paid'] == 0)
    {
        //如果是退款申请, 并且已完成,更新此条记录,扣除相应的余额
        if ($is_paid == '1' && $account['process_type'] == '1')
        {
            $user_account = get_store_surplus($account['user_id']);
            $fmt_amount   = str_replace('-', '', $amount);

            //如果扣除的余额多于此会员拥有的余额，提示
            if ($fmt_amount > $user_account)
            {
                $link[] = array('text' => $_LANG['go_back'], 'href'=>'javascript:history.back(-1)');
                sys_msg($_LANG['surplus_amount_error'], 0, $link);
            }

            update_store_account($id, $amount, $admin_note, $is_paid);

            //更新店家余额数量
            log_account_change($account['store_id'], $amount, 0, 0, 0, $_LANG['surplus_type_1'], ACT_DRAWING);
        }
        elseif ($is_paid == '1' && $account['process_type'] == '0')
        {
            //如果是预付款，并且已完成, 更新此条记录，增加相应的余额
            update_store_account($id, $amount, $admin_note, $is_paid);

            //更新店家余额数量
            store_account_change($account['store_id'], $amount, 0, 0, 0, $_LANG['surplus_type_0'], ACT_SAVING);

        }
        elseif ($is_paid == '0')
        {
            /* 否则更新信息 */
            $sql = "UPDATE " .$ecs->table('store_account'). " SET ".
                   "admin_user    = '$_SESSION[admin_name]', ".
                   "admin_note    = '$admin_note', ".
                   "is_paid       = 0 WHERE id = '$id'";
            $db->query($sql);
        }

        /* 记录管理员日志 */
        admin_log('(' . addslashes($_LANG['check']) . ')' . $admin_note, 'edit', 'store_surplus');

        /* 提示信息 */
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'store_account.php?act=list&' . list_link_postfix();

        sys_msg($_LANG['attradd_succed'], 0, $link);
    }
}

/*------------------------------------------------------ */
//-- ajax帐户信息列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
	//	判断是否为店家管理员
	$is_store_admin = !empty($_SESSION['store_id']) ? 1 : 0;
	$smarty->assign('is_store_admin',    $is_store_admin);
	/* 支付方式的ID */
	$pay_name = $_LANG[alipay];
	$sql = 'SELECT pay_id FROM ' .$GLOBALS['ecs']->table('payment').
	" WHERE pay_name = '$pay_name' AND enabled = 1";
	$pid = $GLOBALS['db']->getOne($sql);
	$smarty->assign('is_store_admin',    $is_store_admin);
	$smarty->assign('pid',    $pid);
	$store_account_money=get_store_surplus($_SESSION['store_id']);
	$smarty->assign('store_account_money',   $store_account_money);
	
    $list = account_list();
    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('store_account_list.htm'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}
/*------------------------------------------------------ */
//-- ajax删除一条信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    /* 检查权限 */
    check_authz_json('store_recharge');
    $id = @intval($_REQUEST['id']);
    $sql = "SELECT s.store_name FROM " . $ecs->table('store') . " AS s, " .
           $ecs->table('store_account') . " AS sa " .
           " WHERE s.store_id = sa.store_id AND sa.id = '$id' ";
    $user_name = $db->getOne($sql);
    $sql = "DELETE FROM " . $ecs->table('store_account') . " WHERE id = '$id'";
    if ($db->query($sql, 'SILENT'))
    {
       admin_log(addslashes($user_name), 'remove', 'store_surplus');
       $url = 'store_account.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
       ecs_header("Location: $url\n");
       exit;
    }
    else
    {
        make_json_error($db->error());
    }
}
/* 店家支付宝进行再付款的操作 */
elseif ($_REQUEST['act'] == 'pay')
{
	include_once(ROOT_PATH . 'includes/lib_clips.php');
	include_once(ROOT_PATH . 'includes/lib_payment.php');
	include_once(ROOT_PATH . 'includes/lib_order.php');

	//变量初始化
	$surplus_id = isset($_GET['id'])  ? intval($_GET['id'])  : 0;
	$payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

	if ($surplus_id == 0)
	{
		ecs_header("Location: admin/store_account.php?act=list\n");
		exit;
	}

	//如果原来的支付方式已禁用或者已删除, 重新选择支付方式
	if ($payment_id == 0)
	{
		ecs_header("Location: user.php?act=account_deposit&id=".$surplus_id."\n");
		exit;
	}

	//获取单条会员帐目信息
	$order = array();
	$order = get_stroe_surplus_info($surplus_id);

	//支付方式的信息
	$payment_info = array();
	$payment_info = payment_info($payment_id);
	//echo $payment_id.'dd'.$payment_info;die;
	/* 如果当前支付方式没有被禁用，进行支付的操作 */
	if (!empty($payment_info))
	{
		//取得支付信息，生成支付代码
		$payment = unserialize_config($payment_info['pay_config']);

		//生成伪订单号
		$order['order_sn'] = $surplus_id;

		//获取需要支付的log_id
		$order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_STORE_SURPLUS);

		$order['user_name']      = $_SESSION['admin_name'];
		$order['surplus_amount'] = $order['amount'];

		//计算支付手续费用
		$payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

		//计算此次预付款需要支付的总金额
		$order['order_amount']   = $order['surplus_amount'] + $payment_info['pay_fee'];

		//如果支付费用改变了，也要相应的更改pay_log表的order_amount
		$order_amount = $db->getOne("SELECT order_amount FROM " .$ecs->table('pay_log')." WHERE log_id = '$order[log_id]'");
		if ($order_amount <> $order['order_amount'])
		{
			$db->query("UPDATE " .$ecs->table('pay_log').
					" SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
		}

		/* 调用相应的支付方式文件 */
		include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

		/* 取得在线支付方式的支付按钮 */
		$pay_obj = new $payment_info['pay_code'];
		$payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

		/* 模板赋值 */
		$smarty->assign('payment', $payment_info);
		$smarty->assign('order',   $order);
		$smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
		$smarty->assign('amount',  price_format($order['surplus_amount'], false));
		$smarty->assign('action',  'act_account');
		assign_query_info();
		$smarty->display('store_transaction.htm');
	}
	/* 重新选择支付方式 */
	else
	{
		include_once(ROOT_PATH . 'includes/lib_clips.php');

		$smarty->assign('payment', get_online_payment_list());
		$smarty->assign('order',   $order);
		$smarty->assign('action',  'account_deposit');
		$smarty->display('store_transaction.htm');
	}

}
/*------------------------------------------------------ */
//-- 会员余额函数部分
/*------------------------------------------------------ */
/**
 * 查询店家余额的数量
 * @access  public
 * @param   int     $store_id        店家ID
 * @return  int
 */
function get_store_surplus($store_id)
{
//     $sql = "SELECT SUM(store_money) FROM " .$GLOBALS['ecs']->table('store_account_log').
//            " WHERE store_id = '$store_id'";
    
//     return $GLOBALS['db']->getOne($sql);
    $sql = "SELECT store_money FROM " .$GLOBALS['ecs']->table('store').
    " WHERE store_id = '$store_id'";
    return $GLOBALS['db']->getOne($sql);
}

/**
 * 更新店家账目明细
 *
 * @access  public
 * @param   array     $id          帐目ID
 * @param   array     $admin_note  管理员描述
 * @param   array     $amount      操作的金额
 * @param   array     $is_paid     是否已完成
 *
 * @return  int
 */
function update_store_account($id, $amount, $admin_note, $is_paid)
{
    $sql = "UPDATE " .$GLOBALS['ecs']->table('store_account'). " SET ".
           "admin_user  = '$_SESSION[admin_name]', ".
           "amount      = '$amount', ".
           "paid_time   = '".gmtime()."', ".
           "admin_note  = '$admin_note', ".
           "is_paid     = '$is_paid' WHERE id = '$id'";
    return $GLOBALS['db']->query($sql);
}

/**
 *
 *
 * @access  public
 * @param
 *
 * @return void
 */
function account_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤列表 */
        //$filter['store_id'] = !empty($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;
    	$filter['store_id'] = isset($_SESSION['store_id']) ? intval($_SESSION['store_id']) : 0;
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['process_type'] = isset($_REQUEST['process_type']) ? intval($_REQUEST['process_type']) : -1;
        $filter['payment'] = empty($_REQUEST['payment']) ? '' : trim($_REQUEST['payment']);
        $filter['is_paid'] = isset($_REQUEST['is_paid']) ? intval($_REQUEST['is_paid']) : -1;
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : local_strtotime($_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (local_strtotime($_REQUEST['end_date']) + 86400);

        $where = " WHERE 1 ";
        if ($filter['store_id'] > 0)
        {
            $where .= " AND sa.store_id = '$filter[store_id]' ";
        }
        if ($filter['process_type'] != -1)
        {
            $where .= " AND sa.process_type = '$filter[process_type]' ";
        }
        else
        {
            $where .= " AND sa.process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
        }
        if ($filter['payment'])
        {
        	$filter['payment'] = json_str_iconv($filter['payment']);
            $where .= " AND sa.payment = '$filter[payment]' ";
        }
        if ($filter['is_paid'] != -1)
        {
            $where .= " AND sa.is_paid = '$filter[is_paid]' ";
        }

        if ($filter['keywords'])
        {
            $where .= " AND s.store_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
            $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('store_account'). " AS sa, ".
                   $GLOBALS['ecs']->table('store') . " AS s " . $where;
        }
        /*　时间过滤　*/
        if (!empty($filter['start_date']) && !empty($filter['end_date']))
        {
            $where .= "AND paid_time >= " . $filter['start_date']. " AND paid_time < '" . $filter['end_date'] . "'";
        }
        
        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('store_account'). " AS sa LEFT JOIN ".
                   $GLOBALS['ecs']->table('store') . " AS s ON sa.store_id=s.store_id" . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        
        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询数据 */
        $sql  = 'SELECT sa.*, s.store_name FROM ' .
            $GLOBALS['ecs']->table('store_account'). ' AS sa LEFT JOIN ' .
            $GLOBALS['ecs']->table('store'). ' AS s ON sa.store_id = s.store_id'.
            $where . "ORDER by " . $filter['sort_by'] . " " .$filter['sort_order']. " LIMIT ".$filter['start'].", ".$filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list AS $key => $value)
    {
        $list[$key]['surplus_amount']       = price_format(abs($value['amount']), false);
        $list[$key]['add_date']             = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
        $list[$key]['process_type_name']    = $GLOBALS['_LANG']['surplus_type_' . $value['process_type']];
     }
    $arr = array('list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}


/**
 * 根据ID获取当前店家余额操作信息
 *
 * @access  public
 * @param   int     $surplus_id  店家余额的ID
 *
 * @return  int
 */
function get_stroe_surplus_info($surplus_id)
{
	$sql = 'SELECT * FROM ' .$GLOBALS['ecs']->table('store_account').
	" WHERE id = '$surplus_id'";

	return $GLOBALS['db']->getRow($sql);
}

/**
 * 记录店家帐户变动
 * @param   int     $store_id        用户id
 * @param   float   $store_money     可用余额变动
 * @param   float   $frozen_money   冻结余额变动
 * @param   int     $rank_points    等级积分变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $change_desc    变动说明
 * @param   int     $change_type    变动类型：参见常量文件
 * @return  void
 */
function store_account_change($store_id, $store_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
{
	/* 插入店家帐户变动记录 */
	$account_log = array(
			'store_id'       => $store_id,
			'store_money'    => $store_money,
			'frozen_money'  => $frozen_money,
			'rank_points'   => $rank_points,
			'pay_points'    => $pay_points,
			'change_time'   => gmtime(),
			'change_desc'   => $change_desc,
			'change_type'   => $change_type
	);

	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('store_account_log'), $account_log, 'INSERT');

	/* 更新店家信息 */
	$sql = "UPDATE " . $GLOBALS['ecs']->table('store') .
	" SET store_money = store_money + ('$store_money')," .
	" frozen_money = frozen_money + ('$frozen_money')," .
	" rank_points = rank_points + ('$rank_points')," .
	" pay_points = pay_points + ('$pay_points')" .
	" WHERE store_id = '$store_id' LIMIT 1";
	$GLOBALS['db']->query($sql);

}
?>