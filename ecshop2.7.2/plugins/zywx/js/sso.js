
var ssoip = "http://sso.tx100.com";
var extraCallback = "";

var doAjaxLogin = function(callback, target)
{
		setCallback(callback);
		flushLoginTicket();
};

//设置登录后的回调方法
var setCallback = function(callbackName) 
{
	extraCallback = callbackName;
};

//由于一个 login ticket 只允许使用一次, 当每次登录需要调用该函数刷新 lt
var flushLoginTicket = function() 
{
	var currentLoc = $('#loginServiceId').val();
	var _services = 'service=' + encodeURIComponent(currentLoc);
	$.ajax({
		url: ssoip+"/login?callback=?",
		dataType: 'json',
		cache: false,
		async: false,
		data: _services+'&get-lt=true',
		success: function(data, textStatus, jqXHR) {
			$('#J_LoginTicket').attr("value",data.retData);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			$("#tan_bg").hide();
			$("#tan_box").hide();
		}
	});
};

// 登录验证函数, 由 onsubmit 事件触发
var loginValidate = function()
{
	var msg = '';  
	if ($.trim($('#j_username').val()).length == 0 )
	{
		msg = "username is not empty";
	}
	else if ($.trim($('#j_password').val()).length == 0 )
	{ 
		msg = "password is not empty";
	}
	
	if (msg != '' && msg.length > 0) 
	{
		$('#J_ErrorMsg').fadeOut().text(msg).fadeIn();  
		return false;  
        // Can't request the login ticket.  
	} 
	else if ($('#J_LoginTicket').val().length == 0)
	{
		$('#J_ErrorMsg').text('Server is busying,Please try again later...');  
		return false;  
	} 
	else
	{
		return true;
	}
};

//appcan网站loginStatus的回调方法
var loginStatusCallback = function (result) 
{
	if(result=='success') 
	{
		$('#J_ErrorMsg').fadeOut().text('login success').fadeIn();
		var email = $.trim($('#j_username').val());
		window.location.href="index.php?act=email_submit&login_email="+email;
	}
	else
	{
		$('#J_ErrorMsg').fadeOut().text('username or password is error').fadeIn();
	    flushLoginTicket();
	}
};