<?php

/**
 * ECSHOP rpc Widget接口函数
 * 
 * $Author: lilixing $
 * $Id: lib_clips.php 15013 2011-12-26 09:31:42Z lilixing $
 */


/**
 *  获取指定用户的收藏商品列表
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表其实位置
 *
 * @return  array   $arr
 */
function zy_get_collection_goods($user_id, $num = 10, $start = 0)
{
	$sql = 'SELECT g.goods_id, g.goods_name,g.goods_desc,g.goods_thumb,g.shop_price,g.market_price '.
           ' FROM ' . $GLOBALS['ecs']->table('collect_goods') . ' AS c' .
		   ' LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g '.
           'ON g.goods_id = c.goods_id '.
           " WHERE c.user_id = '$user_id' ORDER BY c.rec_id DESC";

	$res = $GLOBALS['db'] -> selectLimit($sql, $num, $start);

	$goods_list = array();
	while ($row = $GLOBALS['db']->fetchRow($res))
    {
		$goods_list[$row['goods_id']]['goods_id']      = $row['goods_id'];
        $goods_list[$row['goods_id']]['goods_name']    = $row['goods_name'];
		$goods_list[$row['goods_id']]['goods_desc']    = $row['goods_desc'];
        $goods_list[$row['goods_id']]['goods_thumb']   = $row['goods_thumb'];
        $goods_list[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
        $goods_list[$row['goods_id']]['market_price']   = price_format($row['market_price']);
	}
	return $goods_list;
}

/**
 *  获取指定用户的留言
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表其实位置
 * @return  array   $msg            留言及回复列表
 */
function zy_get_message_list($user_id, $num, $start, $order_id = 0)
{
	$msg = array();
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('feedback'). " WHERE parent_id = 0 AND user_id = '$user_id' AND order_id=0 ORDER BY msg_time DESC" ;

	$res = $GLOBALS['db']->SelectLimit($sql, $num, $start);
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
		$msg[$rows['msg_id']]['msg_id']	 = $rows['msg_id'];
		$msg[$rows['msg_id']]['msg_content'] = nl2br(htmlspecialchars($rows['msg_content']));
        $msg[$rows['msg_id']]['msg_time']    = local_date($GLOBALS['_CFG']['time_format'], $rows['msg_time']);
        $msg[$rows['msg_id']]['msg_type']    = $rows['msg_type'];
		$msg[$rows['msg_id']]['msg_title']   = nl2br(htmlspecialchars($rows['msg_title']));
        $msg[$rows['msg_id']]['message_img'] = $rows['message_img'];
	}
	return $msg;
}

/**
 *  获取指定用户的单条留言详情
 *
 * @access  public
 * @param   int     $msg_id        留言ID

 */
function zy_get_message_info($msg_id)
{
	$msg = array();
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('feedback'). " WHERE msg_id = ".$msg_id;
	$rows = $GLOBALS['db']->getRow($sql);
	if($rows)
	{		
		$msg[$rows['msg_id']]['msg_id']	 = $rows['msg_id'];
		$msg[$rows['msg_id']]['msg_content'] = nl2br(htmlspecialchars($rows['msg_content']));
		$msg[$rows['msg_id']]['msg_time']    = local_date($GLOBALS['_CFG']['time_format'], $rows['msg_time']);
		$msg[$rows['msg_id']]['msg_type']    = $rows['msg_type'];
		$msg[$rows['msg_id']]['msg_title']   = nl2br(htmlspecialchars($rows['msg_title']));
		$msg[$rows['msg_id']]['message_img'] = $rows['message_img'];

		$reply = array();
		$sql   = "SELECT user_name, user_email, msg_time, msg_content".
				 " FROM " .$GLOBALS['ecs']->table('feedback') .
				 " WHERE parent_id = '" . $rows['msg_id'] . "'";

		$reply = $GLOBALS['db']->getRow($sql);
		if ($reply)
		{
			$msg[$rows['msg_id']]['re_user_name']   = $reply['user_name'];
			$msg[$rows['msg_id']]['re_user_email']  = $reply['user_email'];
			$msg[$rows['msg_id']]['re_msg_time']    = local_date($GLOBALS['_CFG']['time_format'], $reply['msg_time']);
			$msg[$rows['msg_id']]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
		}
	}
	return $msg;
}

/**
 *  添加留言函数
 *
 * @access  public
 * @param   array       $message
 *
 * @return  boolen      $bool
 */
function add_message($message)
{
	if (empty($message['msg_title']))
    {
        return false;
    }

	$status = 1 - $GLOBALS['_CFG']['message_check'];
	$message['msg_area'] = isset($message['msg_area']) ? intval($message['msg_area']) : 0;

    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('feedback') .
            " (msg_id, parent_id, user_id, user_name, user_email, msg_title, msg_type, msg_status,  msg_content, msg_time, message_img, order_id, msg_area)".
            " VALUES (NULL, 0, '$message[user_id]', '$message[user_name]', '$message[user_email]', ".
		  " '$message[msg_title]', '$message[msg_type]', '$status', '$message[msg_content]', '".gmtime()."', '', '$message[order_id]', '$message[msg_area]')";
    $GLOBALS['db']->query($sql);

	return true;
}