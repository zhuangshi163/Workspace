<?php
/**
 * 评论接口
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-12-28 上午10:19:30
 * @author     myy
 */

define('IN_ECS', true);
include_once('includes/init.php'); 
$act = empty($_GET['act']) ? 'show_comment': $_GET['act'];
$action_arr = array("show_comment","add_comment");
if(!in_array($act,$action_arr))
{
    $msg = rpcLang('goods.php', 'error_action');
	jsonExit("{\"status\":\"$msg\"}");
}

if($act=="show_comment")
{
	//查询评论信息
    if(!empty ($_REQUEST['goods_id']))
	{
        $goods_id =trim($_REQUEST['goods_id']);
        $page = !empty($_REQUEST['page'])?intval(trim($_REQUEST['page'])):1;
        $page_size = !empty($_REQUEST['page_size'])?intval(trim($_REQUEST['page_size'])):3;
        $comments = zy_assign_comment($goods_id, "comments",$page,$page_size);
        jsonExit($comments);
    }
    else
	{
        $msg = rpcLang('goods.php', 'param_failure1');
        $error['param_failure2'] = $msg;
        jsonExit($error);
    }
    
}else if($act=="add_comment")
{
	//添加评论    
    if(!empty ($_REQUEST['user_id']))
	{
        $goods_id = !empty ($_REQUEST['goods_id'])?trim($_REQUEST['goods_id']):die;
        $user_id =trim($_REQUEST['user_id']);
        $user_name = !empty($_REQUEST['user_name'])?trim($_REQUEST['user_name']):$_SESSION['user_name'];
        $user_name = utf8togbk($user_name);
        $email = !empty($_REQUEST['email']) ? trim($_REQUEST['email']):$_SESSION['email'] ;
        $email = htmlspecialchars($email);
        $user_name = htmlspecialchars($user_name);
        $content = trim($_REQUEST['content']);
        $content = utf8togbk($content);
        $rank = !empty($_REQUEST['rank']) ? trim($_REQUEST['rank']):1 ;
        $msg= zy_add_comment($goods_id,$user_id,$email,$user_name,$content,$rank);
        jsonExit($msg);
    }else
	{
        $msg = rpcLang('goods.php', 'no_user_id');
        $error['no_user_id'] = $msg;
        jsonExit($error);
    }
}


/**
 * 查询评论内容
 *
 * @access  public
 * @params  integer     $id
 * @params  integer     $type
 * @params  integer     $page
 * @params  integer     $size
 * @return  array
 */
function zy_assign_comment($id, $type, $page = 1, $size=3)
{
    /* 取得评论列表 */
    $count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('comment').
           " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0");

	//$size  = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;

    $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') .
            " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0".
            ' ORDER BY comment_id DESC';
    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page-1) * $size);

    $arr = array();
    $ids = '';
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
        $arr[$row['comment_id']]['id']       = $row['comment_id'];
        $arr[$row['comment_id']]['email']    = $row['email'];
        $arr[$row['comment_id']]['username'] = $row['user_name'];
        $arr[$row['comment_id']]['content']  = str_replace('\r\n', '<br />', htmlspecialchars($row['content']));
        $arr[$row['comment_id']]['content']  = nl2br(str_replace('\n', '<br />', $arr[$row['comment_id']]['content']));
        $arr[$row['comment_id']]['rank']     = $row['comment_rank'];
        $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
    }
    //取得已有回复的评论
    if ($ids)
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') .
                " WHERE parent_id IN( $ids )";
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetch_array($res))
        {
            $arr[$row['parent_id']]['re_content']  = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
            $arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $arr[$row['parent_id']]['re_email']    = $row['email'];
            $arr[$row['parent_id']]['re_username'] = $row['user_name'];
        }
    }
    //分页样式
    //$pager['styleid'] = isset($GLOBALS['_CFG']['page_style'])? intval($GLOBALS['_CFG']['page_style']) : 0;
    $pager['page']         = $page;
    $pager['page_size']         = $size;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count;
	/*
    $pager['page_first']   = "javascript:gotoPage(1,$id,$type)";
    $pager['page_prev']    = $page > 1 ? "javascript:gotoPage(" .($page-1). ",$id,$type)" : 'javascript:;';
    $pager['page_next']    = $page < $page_count ? 'javascript:gotoPage(' .($page + 1) . ",$id,$type)" : 'javascript:;';
    $pager['page_last']    = $page < $page_count ? 'javascript:gotoPage(' .$page_count. ",$id,$type)"  : 'javascript:;';
    */
	$cmt = array();
    if(!empty ($arr))
	{
        $cmt = array('comments' => $arr, 'pager' => $pager);
    }else
	{
		//$cmt = array("status"=>rpcLang('goods.php', 'no_data'));
    }
    
    return $cmt;
}


/**
 * 添加评论内容
 *
 * @access  public
 * @param   object  $cmt
 * @return  void
 */
function zy_add_comment($id,$user_id,$email,$user_name,$content,$rank)
{
    /* 评论是否需要审核 */
    $status = 1 - $GLOBALS['_CFG']['comment_check'];
    $type = 0;
    /* 保存评论内容 */
    $sql = "INSERT INTO " .$GLOBALS['ecs']->table('comment') .
           "(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, status, parent_id, user_id) VALUES " .
           "('" .$type. "', '" .$id. "', '$email', '$user_name', '" .$content."', '".$rank."', ".gmtime().", '".real_ip()."', '$status', '0', '$user_id')";
    
    $result = $GLOBALS['db']->query($sql);

    return $result;
}

?>
