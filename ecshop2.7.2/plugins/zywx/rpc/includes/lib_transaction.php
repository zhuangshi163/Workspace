<?php

/**
 * ECSHOP rpc Widget接口函数
 * 
 * $Author: lilixing $
 * $Id: lib_transaction.php 15013 2011-12-26 09:31:42Z lilixing $
 */


/**
 * 修改个人资料（Email, 性别，生日)
 *
 * @access  public
 * @param   array       $profile       array_keys(user_id int, email string, sex int, birthday string);
 *
 * @return  boolen      $bool
 */
function zy_edit_profile($profile)
{
    if (empty($profile['user_id']))
    {
        return false;
    }

	$cfg = array();

    $cfg['username'] = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='" . $profile['user_id'] . "'");

    if (isset($profile['sex']))
    {
        $cfg['gender'] = intval($profile['sex']);
    }

    $cfg['email'] = $profile['email'];
	$cfg['bday'] = $profile['birthday'];
    
	if (!$GLOBALS['user']->edit_user($cfg))
    {
        return false;
    }

	/* 过滤非法的键值 */
    $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone');

    foreach ($profile['other'] as $key => $val)
    {
        //删除非法key值
        if (!in_array($key, $other_key_array))
        {
            unset($profile['other'][$key]);
        }
        else
        {
            $profile['other'][$key] =  htmlspecialchars(trim($val)); //防止用户输入javascript代码
        }
    }
	 /* 修改在其他资料 */
    if (!empty($profile['other']))
    {
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $profile['other'], 'UPDATE', "user_id = '$profile[user_id]'");
    }

    return true;

}

/**
 * 修改个人的默认收获地址
 *
 * @access  public
 * @param   int       user_id,address_id
 *
 * @return  boolen      $bool
 */
function zy_update_user_default_address($user_id,$address_id)
{
	$user_id = isset($user_id) ? intval($user_id) : 0;

	$address_id = isset($address_id) ? intval($address_id) : 0;
	
	if ($user_id <=0 || $address_id <=0)
    {
        return false;
    }

	$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET address_id = $address_id WHERE user_id= $user_id " ;
	$GLOBALS['db']->query($sql);

    return true;
}



/**
 * 获得指定国家、省份、城市的名称
 *
 * @access      public
 * @param       int
 * @return      array
 */
function get_region_name($region_id)
{
    $sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id = '$region_id'";

    return $GLOBALS['db']->GetOne($sql);
}


/**
 * 获得收获地址信息
 *
 * @access      public
 * @param       int
 * @return      array
 */
function get_address_info($address_id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('user_address') ." WHERE address_id = '$address_id'";

    return $GLOBALS['db']->getRow($sql,true);
}


/**
 *  获取用户已付过款的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function zy_get_user_orders($user_id, $num = 10, $start = 0)
{
    /* 取得订单列表 */
    $arr    = array();

	$sql = "SELECT order_id, order_sn, add_time, " .
           "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
           " FROM " .$GLOBALS['ecs']->table('order_info') .
           " WHERE user_id = '$user_id' ORDER BY add_time DESC";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

	while ($row = $GLOBALS['db']->fetchRow($res))
    {
		$arr[] = array('order_id'       => $row['order_id'],
                       'order_sn'       => $row['order_sn'],
                       'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
                       'total_fee'      => price_format($row['total_fee'], false)
				);
	}
	
	return $arr;
}



/**
 *  获取指订单的详情
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return   arr        $order          订单所有信息的数组
 */
function zy_get_order_detail($order_id, $user_id = 0)
{
	include_once(RPC_ROOT . 'includes/lib_order.php');

    $order_id = intval($order_id);
    if ($order_id <= 0)
    {
        return false;
    }

	//得到订单信息
	$order = order_info($order_id);

	if($user_id >0 && $user_id != $order['user_id'])
	{
		return false;
	}

	return $order;

}
    


/**
 * 取消一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return void
 */
function zy_cancel_order($order_id, $user_id = 0)
{
    
    
    
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM " .$GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id'";
    $order = $GLOBALS['db']->GetRow($sql);

    if (empty($order))
    {
        $msg = rpcLang('user.php', 'order_exist');
        $code = '1';
        $result = array($msg,$code);
        return $result;
        exit;
    }

    // 如果用户ID大于0，检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id)
    {
        $msg = rpcLang('user.php', 'no_priv');
        $code = '1';
        $result = array($msg,$code);
        return $result;
        exit;
    }

    // 订单状态只能是“未确认”或“已确认”
    if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED)
    {
        $msg = rpcLang('user.php', 'current_os_not_unconfirmed');
        $code = '1';
        $result = array($msg,$code);
        return $result;
        exit;
    }

    //订单一旦确认，不允许用户取消
    if ( $order['order_status'] == OS_CONFIRMED)
    {
        $msg = rpcLang('user.php', 'current_os_already_confirmed');
        $code = '1';
        $result = array($msg,$code);
        return $result;
        exit;
    }

    // 发货状态只能是“未发货”
    if ($order['shipping_status'] != SS_UNSHIPPED)
    {
        $msg = rpcLang('user.php', 'current_ss_not_cancel');
         $code = '1';
        $result = array($msg,$code);
        return $result;
        exit;
    }

    // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
    if ($order['pay_status'] != PS_UNPAYED)
    {
        $msg = rpcLang('user.php', 'current_ps_not_cancel');
        $code = '1';
        $result = array($msg,$code);
        return $result;
        exit;
    }

    //将用户订单设置为取消
    $sql = "UPDATE ".$GLOBALS['ecs']->table('order_info') ." SET order_status = '".OS_CANCELED."' WHERE order_id = '$order_id'";
    if ($GLOBALS['db']->query($sql))
    {
        /* 记录log */
        order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED,$GLOBALS['_LANG']['buyer_cancel'],'buyer');
        /* 退货用户余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['surplus'] > 0)
        {
            $change_desc = sprintf(rpcLang('user.php', 'return_surplus_on_cancel'), $order['order_sn']);
            log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
        }
        if ($order['user_id'] > 0 && $order['integral'] > 0)
        {
            $change_desc = sprintf(rpcLang('user.php', 'return_integral_on_cancel'), $order['order_sn']);
            log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
        }
        if ($order['user_id'] > 0 && $order['bonus_id'] > 0)
        {
            change_user_bonus($order['bonus_id'], $order['order_id'], false);
        }

        /* 如果使用库存，且下订单时减库存，则增加库存 */
        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE)
        {
            change_order_goods_storage($order['order_id'], false, 1);
        }

        /* 修改订单 */
        $arr = array(
            'bonus_id'  => 0,
            'bonus'     => 0,
            'integral'  => 0,
            'integral_money'    => 0,
            'surplus'   => 0
        );
        update_order($order['order_id'], $arr);
        
		$code = '0';
        $result = array(1,$code);
        return $result;
    }
    else
    {
        $code = '1';
        $result = array(0,$code);
        return $result;
    }

}
