<?php
include_once ('includes/modules/comment.model.php');
/**
 * 评论接口
 */
class CommentAction {
	public function index () {
		$this->show_comment();
	}
	//获得商品的评论
	public function show_comment () {
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
	}
	//添加评论
	public function add_comment (){
		//添加评论    
	    if(!empty ($_REQUEST['user_id']))
		{
	        $goods_id = !empty ($_REQUEST['goods_id'])?trim($_REQUEST['goods_id']):die;
	        $user_id =trim($_REQUEST['user_id']);
	        $user_name = !empty($_REQUEST['user_name'])?trim($_REQUEST['user_name']):$_SESSION['user_name'];
	        $content = trim($_REQUEST['content']);
	        $content = gbktoutf8($content);
			if(EC_CHARSET == 'utf-8'){
				$user_name = gbktoutf8($user_name);
				$content = gbktoutf8($content);
			}else{
				$user_name =  utf8togbk($user_name);
				$content = utf8togbk($content);
			}
	        $email = !empty($_REQUEST['email']) ? trim($_REQUEST['email']):$_SESSION['email'] ;
	        $email = htmlspecialchars($email);
	        $user_name = htmlspecialchars($user_name);	        $rank = !empty($_REQUEST['rank']) ? trim($_REQUEST['rank']):1 ;
	        $msg= zy_add_comment($goods_id,$user_id,$email,$user_name,$content,$rank);
	        jsonExit($msg);
	    }else
		{
	        $msg = rpcLang('goods.php', 'no_user_id');
	        $error['no_user_id'] = $msg;
	        jsonExit($error);
	    }
	}
	


}