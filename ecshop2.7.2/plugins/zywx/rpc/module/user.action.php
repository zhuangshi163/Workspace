<?php
/**
 * 用户中心
 *
 */
class UserAction {
	
	public function index () {
		$this->login();		
	}
	//用户登陆
	public  function login () {
		if(EC_CHARSET == 'utf-8'){
			$user_name = empty($_GET['username']) ? '': gbktoutf8($_GET['username']);
		}else{
			$user_name = empty($_GET['username']) ? '': utf8togbk($_GET['username']);
		}
		$pwd = empty($_GET['pwd']) ? '': $_GET['pwd'];
		if(empty($user_name) || empty($pwd))
		{
			$msg = rpcLang('user.php', 'login_failure');
		}
		else
		{
			$user =& init_users();
			if ($user->check_user($user_name, $pwd) > 0) 
			{
				$user->set_session($user_name);
				$msg = rpcLang('user.php', 'login_success');
				$user_id = intval($_SESSION['user_id']);
				jsonExit("{\"status\":\"$msg\",\"user_name\":\"$user_name\",\"user_id\":\"$user_id\",\"code\":\"0\"}");
			}else
			{
				$msg = rpcLang('user.php', 'login_failure');
				jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");	
			}
		}
	}
	//账户信息
	public function profile () {
		checkLogin();
		include_once(RPC_ROOT . 'includes/lib_transaction_ec.php');
		$user_id = intval($_SESSION['user_id']);
		$user_profile = get_profile($user_id);
		$user_profile['user_id'] = $user_id;
		//var_dump($user_profile);exit;
		jsonExit($user_profile);
	}
	//显示会员注册界面 
	public function register () {
		global $ecs,$db,$_CFG;
		require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
	    if (!isset($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
	    {
	        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
	    }	
	    /* 取出注册扩展字段 */
	    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
	    $extend_info_list = $db->getAll($sql);	
	    $captcha = array('enabled'=>0);		
	    /* 验证码相关设置 */
	    if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
	    { 
			  $captcha['enabled'] = 1;
			  $captcha['path'] = 'captcha.php';
			  $captcha['param'] = 'is_login';
	    }
	    $result = array('extend_info_list'=>$extend_info_list,'passwd_questions'=>$_LANG['passwd_questions'],'captcha'=>$captcha);
	    jsonExit($result);
	}	
	//注册会员的处理 
	public function act_register () {
		include_once(RPC_ROOT . 'includes/lib_passport.php');
		if(EC_CHARSET == 'utf-8'){
			$username = empty($_GET['username']) ? '': gbktoutf8($_GET['username']);
			$passwd_answer = isset($_GET['passwd_answer']) ? gbktoutf8(trim($_GET['passwd_answer'])) : '';
		}else{
			$username = empty($_GET['username']) ? '': utf8togbk($_GET['username']);
			$passwd_answer = isset($_GET['passwd_answer']) ? utf8togbk(trim($_GET['passwd_answer'])) : '';
		}
		$realname = empty($_GET['realname']) ? '': trim($_GET['realname']);
        $password = isset($_GET['pwd']) ? trim($_GET['pwd']) : '';
        $email    = isset($_GET['email']) ? trim($_GET['email']) : '';
        $store_id    = isset($_GET['email']) ? trim($_GET['store']) : '';
        $other['msn'] = isset($_GET['extend_field1']) ? $_GET['extend_field1'] : '';
        $other['qq'] = isset($_GET['extend_field2']) ? $_GET['extend_field2'] : '';
        $other['office_phone'] = isset($_GET['extend_field3']) ? $_GET['extend_field3'] : '';
        $other['home_phone'] = isset($_GET['extend_field4']) ? $_GET['extend_field4'] : '';
        $other['mobile_phone'] = isset($_GET['extend_field5']) ? $_GET['extend_field5'] : '';
        $sel_question = empty($_GET['sel_question']) ? '' : $_GET['sel_question'];
        $platform  = isset($_GET['platform']) ? $_GET['platform']:'';
        $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
		global $ecs,$db;
        if(empty($_GET['agreement']))
        {
            $msg = rpcLang('user.php', 'agreement');
			$code = '1';
			jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        if ($store_id==-1)
        {
        	$msg = rpcLang('user.php', 'store_empty');
        	$code = '1';
        	jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        if ($realname==''||$realname==null)
        {
        	$msg = rpcLang('user.php', 'realname_empty');
        	$code = '1';
        	jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        if (strlen($username) < 3)
        {
            $msg = rpcLang('user.php', 'username_shorter');
			$code = '1';
			jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        if (!$this->is_registered($username)) {
        	$msg = rpcLang('user.php', 'username_is_registered');
			$code = '1';
			jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        if (strlen($password) < 6)
        {
            $msg = rpcLang('user.php', 'password_shorter');
			$code = '1';
			jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        if (strpos($password, ' ') > 0)
        {
            $msg = rpcLang('user.php', 'passwd_balnk');
			$code = '1';
			jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
        }
        /*if (!check_email($email)) {
        	$msg = rpcLang('user.php', 'email_is_registered');
			$code = '1';
			jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");        
        }*/
        /* 验证码检查 */
        /*if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
        {
            if (empty($_GET['captcha']))
            {
                //show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            	$msg = rpcLang('user.php', 'captcha_empty');
				$code = '1';
				jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
            }*/

            /* 检查验证码 */
          /*  include_once(RPC_ROOT.'includes/cls_captcha.php');
            $validator = new captcha();
            if (!$validator->check_word(trim($_GET['captcha'])))
            {
                $msg = rpcLang('user.php', 'captcha_err');
				$code = '1';
				jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
            }
        }*/
        if (register($username, $password, $email, $store_id, $realname,$other) !== false)
        {
            /*把新注册用户的扩展信息插入数据库*/
            $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
            $fields_arr = $db->getAll($sql);

            $extend_field_str = '';    //生成扩展字段的内容字符串
            foreach ($fields_arr AS $val)
            {
                $extend_field_index = 'extend_field' . $val['id'];
                if(!empty($_POST[$extend_field_index]))
                {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
                    $extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
                }
            }
            $extend_field_str = substr($extend_field_str, 0, -1);

            if ($extend_field_str)      //插入注册扩展数据
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
				$db->query($sql);
            }

           /*注册关联表*/
//            if ($platform) 
//            {
//				$reg_time=strtotime(date("Y-m-d H:i:s",time()));
//				$sql = "INSERT INTO " . $ecs->table ( 'user_mobile' ) . " (`user_id`, `referer`,`reg_time`) VALUES (".$_SESSION ['user_id'].",'".$platform."',".$reg_time.")";
//				$db->query ($sql);
//            }		

            /* 写入密码提示问题和答案 */
            if (!empty($passwd_answer) && !empty($sel_question))
            {
                $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
                $db->query($sql);
            }
        	$msg = rpcLang('user.php', 'registered_suc');
			$code = '0';
            jsonExit("{\"status\":\"$msg\",\"username\":\"$username\",\"code\":\"$code\"}"); 
        }
        else
        {
            $msg = rpcLang('user.php', 'registered_err');
			$code = '1';
            jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");        
        }
	} 
	/* 验证用户注册用户名是否可以注册 */
	function  is_registered ($username) {
	 	include_once(RPC_ROOT . 'includes/lib_passport.php');
	 	$username = json_str_iconv($username);
	 	global $user;
	    if ($user->check_user($username) || admin_registered($username))
	    {
	        return false;
	    }
	    else
	    {
	        return true;
	    }
	
	}
	/* 验证用户邮箱地址是否被注册 */
	function check_email ($email) {
		global $user;
		if ($user->check_email($email))
	    {
	        return false;
	    }
	    else
	    {
	        return true;
	    }
	}
	//注销
	public function logout () {
		global $user;
		$user->set_session();
		if(empty($_SESSION['user_id']))
		{
			$msg = rpcLang('user.php', 'logout_success');
			jsonExit("{\"status\":\"$msg\",\"code\":\"0\"}");
		}else
		{
			$msg = rpcLang('user.php', 'logout_failure');
			jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
	}
	//修改会员密码
	public function act_edit_password () {
		checkLogin();
		include_once(RPC_ROOT . 'includes/lib_passport.php');
		$old_password = isset($_GET['old_password']) ? trim($_GET['old_password']) : null;
		$new_password = isset($_GET['new_password']) ? trim($_GET['new_password']) : '';
		$user =& init_users();
		$user_id = intval($_SESSION['user_id']);
		if(isset($_SESSION['user_name']))
		{
			$user_name = $_SESSION['user_name'];
		}else
		{
			$user_info = $user->get_profile_by_id($user_id);
			$user_name = $user_info['user_name'];
		}
		if($user->check_user($user_name, $old_password))
		{
			if ($user->edit_user(array('username'=> $user_name, 'old_password'=>$old_password, 'password'=>$new_password), 1))
			{
				$msg = rpcLang('user.php','edit_password_success');
				$code = '0';
			}else
			{
				$msg = rpcLang('user.php','edit_password_failure');
				$code = '1';
			}
	    
		}else
		{
			$msg = rpcLang('user.php','edit_password_failure');
			$code = '1';
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//修改个人信息
	public function  act_edit_profile () {
		global $ecs,$db;
		checkLogin();
		$user_id = intval($_SESSION['user_id']);
		include_once('includes/lib_transaction.php');
	
		$birthday = trim($_GET['birthdayYear']).'-'.trim($_GET['birthdayMonth']).'-'.trim($_GET['birthdayDay']);
		$email = isset($_GET['email']) ? trim($_GET['email']): '';
		$other['msn'] = $msn = isset($_GET['extend_field1']) ? trim($_GET['extend_field1']) : '';
		$other['qq']  = $qq  = isset($_GET['extend_field2']) ? trim($_GET['extend_field2']) : '';
		$other['office_phone'] = $office_phone = isset($_GET['extend_field3']) ? trim($_GET['extend_field3']) : '';
		$other['home_phone'] = $home_phone = isset($_GET['extend_field4']) ? trim($_GET['extend_field4']) : '';
		$other['mobile_phone'] = $mobile_phone = isset($_GET['extend_field5']) ? trim($_GET['extend_field5']) : '';
	
		/* 更新用户扩展字段的数据 */
		$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';  //读出所有扩展字段的id
		$fields_arr = $db->getAll($sql);
	
		foreach ($fields_arr AS $val)       //循环更新扩展用户信息
		{
			$extend_field_index = 'extend_field' . $val['id'];
			if(isset($_GET[$extend_field_index]))
			{
				$temp_field_content = strlen($_GET[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_GET[$extend_field_index]), 0, 99) : htmlspecialchars($_GET[$extend_field_index]);
	
				$sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
	
				if ($db->getOne($sql))      //如果之前没有记录，则插入
				{
					$sql = 'UPDATE '.$ecs->table('reg_extend_info')." SET content='$temp_field_content' WHERE reg_field_id='$val[id]' AND user_id = '$user_id'";
				}
				else
				{
					$sql = 'INSERT INTO '.$ecs->table('reg_extend_info')." (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
				}
				$db->query($sql);
			}
		}
	
		if (!empty($office_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $office_phone ))
		{
			$msg = rpcLang('user.php', 'office_phone_invalid');
			jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
	
		if (!empty($home_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $home_phone) )
		{
			 $msg = rpcLang('user.php', 'home_phone_invalid');
			 jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
		if (!is_email($email))
		{
			$msg = rpcLang('user.php', 'msg_email_format');
			jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
		if (!empty($msn) && !is_email($msn))
		{
			$msg = rpcLang('user.php', 'msn_invalid');
			jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
		if (!empty($qq) && !preg_match('/^\d+$/', $qq))
		{
			$msg = rpcLang('user.php', 'qq_invalid');
			jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
		if (!empty($mobile_phone) && !preg_match('/^[\d-\s]+$/', $mobile_phone))
		{
			$msg = rpcLang('user.php', 'mobile_phone_invalid');
			jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
		}
	
		$profile  = array
		(
			'user_id'  => $user_id,
			'email'    => isset($_GET['email']) ? trim($_GET['email']) : '',
			'sex'      => isset($_GET['sex'])   ? intval($_GET['sex']) : 0,
			'birthday' => $birthday,
			'other'    => isset($other) ? $other : array()
	    );
	
		if (zy_edit_profile($profile))
		{
			$msg =  rpcLang('user.php', 'edit_profile_success');
			$code = '0';
		}
		else
		{
			if ($user->error == ERR_EMAIL_EXISTS)
			{
				$msg =  rpcLang('user.php', 'email_exist');
				$code = '1';
			}
			else
			{
				$msg =  rpcLang('user.php', 'edit_profile_failed');
				$code = '1';
			}
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//收货地址列表
	public function address_list () {
		checkLogin();
		include_once(RPC_ROOT . 'includes/lib_transaction_ec.php');
		include_once('includes/lib_transaction.php');
		$user_id = intval($_SESSION['user_id']);
		$consignee_list = get_consignee_list($user_id);
		$address_list = array();
		//取得国家列表，如果有收货人列表，取得省市区列表
		foreach ($consignee_list AS $key => $consignee)
		{
			$address_id = $consignee['address_id'];
			$consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
			$consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
			$consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;
			$consignee['district'] = isset($consignee['district']) ? intval($consignee['district']) : 0;
			$country  = get_region_name($consignee['country']);
			$province = get_region_name($consignee['province']);
			$city     = get_region_name($consignee['city']);
			$district = get_region_name($consignee['district']);
			$address_list[$address_id]['address']  = $country.'-'.$province.'-'.$city.'-'.$district.'-'.$consignee['address'];
			$address_list[$address_id]['mobile']   = $consignee['mobile'];
			//$address_list[$address_id]['username'] = $_SESSION['user_name'];
			$address_list[$address_id]['username'] = $consignee['consignee'];
		}
		//print_r($address_list);exit;
		jsonExit($address_list);
	}
	//修改默认收获地址
	public function update_user_default_address () {
		checkLogin();
		$user_id = intval($_SESSION['user_id']);
		$address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : 0;	
		if(empty($address_id))
		{
			$msg = rpcLang('user.php', 'address_id_is_empty');
			$code = '1';
		}else
		{
			include_once('includes/lib_transaction.php');
			if(zy_update_user_default_address($user_id,$address_id))
			{
				$msg =  rpcLang('user.php', 'update_user_address_success');
				$code = '0';
			}else
			{
				$msg =  rpcLang('user.php', 'update_user_address_failure');
				$code = '1';
			}
		}
	
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//得到单条收货信息
	public function get_address_info () {
		global $ecs,$db;
		checkLogin();
		$address_id = isset($_GET['address_id'])? intval($_GET['address_id']) :0;
		if(empty($address_id))
		{
			$msg = rpcLang('user.php', 'address_id_is_empty');
			jsonExit("{\"status\":\"$msg\"}");
		}else
		{
			include_once('includes/lib_transaction.php');
			$address = get_address_info($address_id);
			$country_id = $address['country'];
			$province_id = $address['province'];
			$city_id = $address['city'];
			$district_id = $address['district'];
			$arr = array($country_id,$province_id,$city_id,$district_id);
			$result = array (); 
			foreach ($arr as $key => $val) {
				$sql = "select `region_name` from".$ecs->table('region')."where `region_id`= {$val}";
				$region_name = $db->getOne($sql);
				array_push($result,$region_name);
			}
			$address['country_name'] = $result[0];
			$address['province_name'] = $result[1];
			$address['city_name'] = $result[2];
			$address['district_name'] = $result[3];
			$sql = "select `user_name` from".$ecs->table('users')."where `user_id`= {$address['user_id']}";
		    $user_name = $db->getOne($sql); 
		    if(!$user_name){
		    	$msg = rpcLang('user.php', 'get_user_name_failure');
		    	jsonExit("{\"status\":\"$msg\"}");
		    }
		    $address['user_name'] = $user_name ;
			//var_dump($address);exit;
			jsonExit($address);
		}
	}
	//编辑[添加]收获地址信息
	public function act_edit_address (){
		checkLogin();	
		include_once(RPC_ROOT . 'includes/lib_transaction_ec.php');
		$user_id = intval($_SESSION['user_id']);
		$address = array(
			'user_id'    => $user_id,
			'address_id' => intval($_GET['address_id']),
			'country'    => isset($_GET['country'])   ? intval($_GET['country'])  : 0,
			'province'   => isset($_GET['province'])  ? intval($_GET['province']) : 0,
			'city'       => isset($_GET['city'])      ? intval($_GET['city'])     : 0,
			'district'   => isset($_GET['district'])  ? intval($_GET['district']) : 0,
			'email'      => isset($_GET['email'])     ? trim($_GET['email'])      : '',
			'tel'        => isset($_GET['tel'])       ? make_semiangle(trim($_GET['tel'])) : '',
			'mobile'     => isset($_GET['mobile'])    ? make_semiangle(trim($_GET['mobile'])) : '',
			'best_time'  => isset($_GET['best_time']) ? trim($_GET['best_time'])  : '',
			'zipcode'       => isset($_GET['zipcode'])       ? make_semiangle(trim($_GET['zipcode'])) : '',
		);
		if(EC_CHARSET == 'utf-8'){
			$address['address'] = isset($_GET['address'])   ? gbktoutf8(trim($_GET['address'])) : '';
			$address['consignee'] = isset($_GET['consignee']) ? gbktoutf8(trim($_GET['consignee']))  : '';
			$address['sign_building'] = isset($_GET['sign_building']) ? gbktoutf8(trim($_GET['sign_building'])) : '';
		}else{
			$address['address'] = isset($_GET['address'])   ? utf8togbk(trim($_GET['address'])) : '';
			$address['consignee'] = isset($_GET['consignee']) ? utf8togbk(trim($_GET['consignee']))  : '';
			$address['sign_building'] = isset($_GET['sign_building']) ? utf8togbk(trim($_GET['sign_building'])) : '';
		}
		$default = isset($_GET['default']) ? trim($_GET['default'])  : false;
		
		if (update_address($address,$default))
		{
			$msg = rpcLang('user.php', 'address_update_success');
			$code = '0';
		}else
		{
			$msg = rpcLang('user.php', 'address_update_failure');
			$code = '1';
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//删除单条收货信息
	public function drop_consignee () {
		checkLogin();
		include_once(RPC_ROOT . 'includes/lib_transaction_ec.php');
		$consignee_id = empty($_GET['address_id']) ? 0: intval($_GET['address_id']);
		if (drop_consignee($consignee_id))
		{
			$msg = rpcLang('user.php', 'address_delete_success');
			$code = '0';
		}else
		{
			$msg = rpcLang('user.php', 'address_delete_failure');
			$code = '1';
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
		
	}
	//获取地区列表信息
	public function get_regions (){
		$type   = empty($_GET['type'])   ? 0 : intval($_GET['type']);
		$parent = empty($_GET['parent']) ? 0 : intval($_GET['parent']);
		$area_arr = get_regions($type,$parent);
		if(empty($area_arr))
		{
			$msg = rpcLang('user.php', 'get_regions_failure');
			jsonExit("{\"status\":\"$msg\"}");
		}else
		{
			jsonExit($area_arr);
		}
	}
	//我的订单列表
	public function order_list () {
		global $ecs,$db;
		checkLogin();
		include_once('includes/lib_transaction.php');
		$user_id = intval($_SESSION['user_id']);
		$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
		$record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'");
		$pager  = get_pager('user.php', $_GET, $record_count, $page);
		$order_list = zy_get_user_orders($user_id, $pager['size'], $pager['start']);
		$arr = array();
		$count = count($order_list);
		foreach ($order_list as $key=>$val) {
			$order_detail = zy_get_order_detail($val['order_id'], $user_id);		
			$order_status = $order_detail['order_status'];
			$shipping_status = $order_detail['shipping_status'];
			$pay_status = $order_detail['pay_status'];			
			$result = array("order_status"=>$order_status,"shipping_status"=>$shipping_status,"pay_statu"=>$pay_status);
			array_push($arr,$result);
		}
		for ($i=0;$i<$count;$i++){
			array_push($order_list[$i],$arr[$i]);
		}
		//var_dump($order_list);exit;
		$order = array('pager'=>$pager,'orderlist'=>$order_list);
		jsonExit($order);
	}
	//订单详情
	public function order_detail () {
		global $ecs,$db;
		checkLogin();
		include_once('includes/lib_transaction.php');
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$user_id  = intval($_SESSION['user_id']);
		$order = zy_get_order_detail($order_id, $user_id);
		if(empty($order))
		{
			$msg = rpcLang('user.php', 'get_orders_failure');
			jsonExit("{\"status\":\"$msg\"}");
		}else
		{    			
			$country_id = $order['country'];
			$province_id = $order['province'];
			$city_id = $order['city'];
			$district_id = $order['district'];
			$arr = array($country_id,$province_id,$city_id,$district_id);
			$result = array (); 
			foreach ($arr as $key => $val) {
				$sql = "select `region_name` from".$ecs->table('region')."where `region_id`= {$val}";
				$region_name = $db->getOne($sql);
				array_push($result,$region_name);
			}
			$order['country_name'] = $result[0];
			$order['province_name'] = $result[1];
			$order['city_name'] = $result[2];
			$order['district_name'] = $result[3];
			/* 订单商品 */
			$goods_list = order_goods($order_id);
			foreach ($goods_list AS $key => $value)
			{
				$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
				$goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
				$goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
			}
			$order_goods = array('order'=>$order,'goods_list'=>$goods_list);
			jsonExit($order_goods);
		}
	}
	//我的收藏列表
	public function collection_list () {
		global $ecs,$db;
		checkLogin();
		include_once('includes/lib_clips.php');
		$user_id = intval($_SESSION['user_id']);
		$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
		$record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('collect_goods')." WHERE user_id='$user_id'");
		$pager = get_pager('user.php', $_GET, $record_count, $page);
		$product_collect_list = zy_get_collection_goods($user_id, $pager['size'], $pager['start']);
		$arr_key = array_keys($product_collect_list);
		$count = count ($product_collect_list);
		$result = array();
		foreach ($arr_key as $val) {
		    $sql = "select `rec_id` from".$ecs->table('collect_goods')."where `goods_id`= {$val} and `user_id` ={$user_id}";
		    $rec_id = $db->getOne($sql);
		    array_push($result,$rec_id);
		}
		$pro_arr = 0;
		$product_collect_list = array_values($product_collect_list);
		foreach ($product_collect_list as $key=>$val) {
			$product_collect_list[$key]['rec_id'] = $result[$pro_arr];
			$pro_arr++;
		}
		if(empty($product_collect_list))
		{
			$msg = rpcLang('user.php', 'no_collect_product');
			jsonExit("{\"status\":\"$msg\"}");
		}else
		{
			$collect_list = array('pager'=>$pager,'orderlist'=>$product_collect_list);
			jsonExit($collect_list);
		}
	}
	//删除收藏商品
	public function delete_collection () {
		global $ecs,$db;
		checkLogin();
		$collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : 0;
		if ($collection_id > 0)
		{
			$user_id = intval($_SESSION['user_id']);
		    if($db->getOne('SELECT rec_id FROM '.$ecs->table('collect_goods'). " WHERE rec_id='$collection_id' AND user_id ='$user_id'"))
			{
				$db->query('DELETE FROM ' .$ecs->table('collect_goods'). " WHERE rec_id='$collection_id' AND user_id ='$user_id'" );
				$msg = rpcLang('user.php', 'delete_collect_product_success');
				$code = '0';
			}else 
			{
				$msg = rpcLang('user.php', 'collect_product_has_delete');
				$code = '1';
			}
		}else
		{
			$msg = rpcLang('user.php', 'delete_collect_product_failure');
			$code = '1';
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//我的留言列表
	public function message_list () {
		global $ecs,$db;
		checkLogin();
		include_once('includes/lib_clips.php');
		$user_id = intval($_SESSION['user_id']);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback')." WHERE parent_id = 0 AND user_id = '$user_id' AND order_id=0";
		$record_count = $db->getOne($sql);
		$pager = get_pager('user.php',$_GET, $record_count, $page, 3);
		$message_list = zy_get_message_list($user_id,$pager['size'], $pager['start']);
		if(empty($message_list))
		{
			$msg = rpcLang('user.php', 'no_message_list');
			jsonExit("{\"status\":\"$msg\"}");
		}else
		{
			$message_list_arr = array('pager'=>$pager,'messagelist'=>$message_list);
			jsonExit($message_list_arr);
		}
	}
	//获取留言详情
	public function message_info () {
		checkLogin();
		$msg_id = isset($_GET['msg_id']) ? intval($_GET['msg_id']): 0 ;
		include_once('includes/lib_clips.php');
		$message_info = zy_get_message_info($msg_id);
		if(empty($message_info))
		{
			$msg = rpcLang('user.php', 'get_message_failure');
			jsonExit("{\"status\":\"$msg\"}");
		}else
		{
			jsonExit($message_info);
		}
	}
	//添加留言
	public function act_add_message () {
		checkLogin();
		include_once('includes/lib_clips.php');
		$user_id = intval($_SESSION['user_id']);
		$message = array(
			'user_id'     => $user_id,
			'user_name'   => $_SESSION['user_name'],
			'user_email'  => $_SESSION['email'],
			'msg_type'    => isset($_GET['msg_type']) ? intval($_GET['msg_type']) : 0,
			'order_id'	  => empty($_GET['order_id']) ? 0 : intval($_GET['order_id'])
	    );
		if(EC_CHARSET == 'utf-8'){
			$message['msg_title'] = isset($_GET['msg_title']) ? gbktoutf8(trim($_GET['msg_title'])) : '';
			$message['msg_content'] = isset($_GET['msg_content']) ? gbktoutf8(trim($_GET['msg_content'])) : '';
		}else{
			$message['msg_title'] = isset($_GET['msg_title']) ? utf8togbk(trim($_GET['msg_title'])) : '';
			$message['msg_content'] = isset($_GET['msg_content']) ? utf8togbk(trim($_GET['msg_content'])) : '';
		}
		if(add_message($message))
		{
			$msg = rpcLang('user.php', 'add_message_success');
			$code = '0';
		}else
		{
			$msg = rpcLang('user.php', 'add_message_failure');
			$code = '1';
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//删除留言
	public function del_msg() {
		global $ecs,$db;
		checkLogin();
		$id = isset($_GET['msg_id']) ? intval($_GET['msg_id']) : 0;
		if ($id > 0)
		{
			$user_id = intval($_SESSION['user_id']);
			$sql = 'SELECT user_id FROM ' .$ecs->table('feedback'). " WHERE msg_id = '$id' LIMIT 1";
			$row = $db->getRow($sql);
	
			if ($row && $row['user_id'] == $user_id)
			{
				$sql = "DELETE FROM " .$ecs->table('feedback'). " WHERE msg_id = '$id' OR parent_id = '$id'";
				$db->query($sql);
				$msg = rpcLang('user.php', 'del_message_success');
				$code = '0';
			}else
			{
				$msg = rpcLang('user.php', 'del_message_failure');
				$code = '1';
			}
		}else
		{
			$msg = rpcLang('user.php', 'del_message_failure');
			$code = '1';
		}
		jsonExit("{\"status\":\"$msg\",\"code\":\"$code\"}");
	}
	//取消订单 
	public function cancel_order () {
		require(RPC_ROOT . 'includes/lib_order.php');
	    include_once('includes/lib_transaction.php');	    
	    checkLogin();
	    $user_id = intval($_SESSION['user_id']);	    
	    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
	    $msg = zy_cancel_order($order_id, $user_id);	    
	    jsonExit("{\"status\":\"$msg[0]\",\"code\":\"$msg[1]\"}");
	}

}
