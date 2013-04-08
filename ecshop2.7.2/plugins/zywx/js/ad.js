

var host = window.location.host;
var sub =  host == '192.168.1.208' ? '/ecshop' : '';
var appcan_ec_url = sub + '/plugins/zywx/appcan_ec.php';
var web_name = web_name || 'Ecshop';

document.write('<style type="text/css">');
document.write('#msg_win{border:1px solid #ccc;background:#EAEAEA;width:250px;position:absolute;right:2;margin:0px;display:none;overflow:hidden;z-index:99;}');
document.write('#msg_win .icos{position:absolute;top:2px;*top:0px;right:2px;z-index:9;}');
document.write('.icos a{float:left;color:#833B02;margin:1px;text-align:center;text-decoration:none;font-family:webdings;}');
document.write('.icos a:hover{color:#fff;}');
document.write('#msg_title{background:#ddd;border-bottom:1px solid #ccc;border-top:1px solid #FFF;border-left:1px solid');
document.write('#FFF;color:#000;height:25px;line-height:25px;text-indent:5px;}');
document.write('#msg_content{margin:2px;width:300px;height:157px;overflow:hidden;}');
document.write('</style>');

document.write('<div id="msg_win" style="display:block;top:490px;right:0px;visibility:visible;opacity:1;">');
document.write('<div class="icos">');
document.write('<a id="msg_min"	  title="minimize" href="javascript:void 0">-</a>');
document.write('<a id="msg_close" title="close"   href="javascript:void 0">×</a>');
document.write('</div>');
document.write('<div id="msg_title">'+web_name+'</div>');
document.write('<div id="msg_content">');
document.write('<a href="'+appcan_ec_url+'" target="_blank">');
document.write('<img src="./plugins/zywx/images/ec_img.jpg" width="250" height="157" border="0">');
document.write('</a>');
document.write('</div>');
document.write('</div>');

var Message={

//最小化与恢复状态切换
set: function()
{
	var set=this.minbtn.status == 1?[0,1,'block',this.char[0],'最小化']:[1,0,'none',this.char[1],'恢复'];
	this.minbtn.status=set[0];
	this.win.style.borderBottomWidth=set[1];
	this.content.style.display =set[2];
	this.minbtn.innerHTML =set[3]
	this.minbtn.title = set[4];
	this.win.style.top = this.getY().top;
},
//关闭
close: function() 
{
	this.win.style.display = 'none';
	window.onscroll = null;
},
//设置透明度
setOpacity: function(x) 
{
	var v = x >= 100 ? '': 'Alpha(opacity=' + x + ')';
	this.win.style.visibility = x<=0?'hidden':'visible';//IE有绝对或相对定位内容不随父透明度变化的bug
	this.win.style.filter = v;
	this.win.style.opacity = x / 100;
},
//渐显
show: function() 
{
	clearInterval(this.timer2);
	var me = this,fx = this.fx(0, 100, 0.1),t = 0;
	this.timer2 = setInterval(function() {
	t = fx();
	me.setOpacity(t[0]);
	if (t[1] == 0) {clearInterval(me.timer2) }
	},10);
},
//缓冲计算
fx: function(a, b, c) 
{
	var cMath = Math[(a - b) > 0 ? "floor": "ceil"],c = c || 0.1;
	return function() {return [a += cMath((b - a) * c), a - b]}
},
//计算移动坐标
getY: function() 
{
	var d = document,b = document.body, e = document.documentElement;
	var s = Math.max(b.scrollTop, e.scrollTop);
	var h = /BackCompat/i.test(document.compatMode)?b.clientHeight:e.clientHeight;
	var h2 = this.win.offsetHeight;
	return {foot: s + h + h2 + 2+'px',top: s + h - h2 - 2+'px'}
},
//移动动画
moveTo: function(y) 
{
	clearInterval(this.timer);
	var me = this,a = parseInt(this.win.style.top)||0;
	var fx = this.fx(a, parseInt(y));
	var t = 0 ;
	this.timer = setInterval(function() {
	t = fx();
	me.win.style.top = t[0]+'px';
	if (t[1] == 0) {
	clearInterval(me.timer);
	me.bind();
	}
	},10);
},
//绑定窗口滚动条与大小变化事件
bind:function ()
{
	var me=this,st,rt;
	window.onscroll = function() {
	clearTimeout(st);
	clearTimeout(me.timer2);
	me.setOpacity(0);
	st = setTimeout(function() {
	me.win.style.top = me.getY().top;
	me.show();
	},600);
};
window.onresize = function ()
{
	clearTimeout(rt);
	rt = setTimeout(function() {me.win.style.top = me.getY().top},100);
}

},

//创建HTML
init: function() 
{
	function $(id) 
	{
		return document.getElementById(id)};
		this.win=$('msg_win');
		var set={minbtn: 'msg_min',closebtn: 'msg_close',title: 'msg_title',content: 'msg_content'};
		for (var Id in set) {this[Id] = $(set[Id])};
		var me = this;
		this.minbtn.onclick = function() {me.set();this.blur()};
		this.closebtn.onclick = function() {me.close()};
		this.char=navigator.userAgent.toLowerCase().indexOf('firefox')+1?['-','::','×']:['0','2','r'];//FF不支持webdings字体
		this.minbtn.innerHTML=this.char[0];
		this.closebtn.innerHTML=this.char[2];
		//初始化最先位置
		setTimeout(function() 
		{
			me.win.style.display = 'block';
			me.win.style.top = me.getY().foot;
			me.moveTo(me.getY().top);
		},0);
		return this;
	}
};
Message.init();