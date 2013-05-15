function zy_slectmenu(id)
{
  var sl = document.getElementById(id);
  if(sl)
  {
  	var sp = sl.parentElement; //<span>
  	if(sp)
  	{
      var ch = sp.getElementsByTagName("span")[0];
      var ch = ch.getElementsByTagName("span")[0];
      var t = sl.options[sl.selectedIndex].text;
      if(ch)	
      {
        ch.innerHTML=t;
      }	
    }	   				
  }
}

    
    function zy_for(e,cb)
    {
    	var ch = e.currentTarget.previousElementSibling;
    	if(ch.nodeName == "INPUT")
    	{
    		if(ch.type=="checkbox")
    			ch.checked=!ch.checked;
    		if(ch.type=="radio" && !ch.checked)
    			ch.checked="checked";
    			
    	}
    	if(cb)
    		cb(e,ch.checked);
    }
    
    function zy_fold(e,col)
    {
     	var a=e.currentTarget.nextElementSibling;
     	if(a.nodeName=="DIV")
     	{
     		if(col)
     			a.className='ui-collapsible-content ui-collapsible-content-collapsed';
     		else
     			a.className='ui-collapsible-content ';
     	}
    }
    
    function zy_fix(header,footer,run,cb)
    {
        window.uexOnload = function(type){	
        	if(window.navigator.platform=='Win32' && (cb!='undefined' && cb!=null))
        		cb();
         	switch(type)
      		{
      			case 0:
      			{
					//alert("111111")
    	  			//setTimeout(function(){
    	  			if(header)
    	  			{
    	  				
    	  				var ho = document.getElementById(header);
    	  				ho.style.fontSize=window.getComputedStyle(ho,null).fontSize;
    	  				if(ho){
    	  					//uexWindow.openSlibing("1", "2", "head.html", ho.outerHTML, "", ho.offsetHeight);
    	  					uexWindow.openSlibing("1", "2", "head.html", ho.outerHTML, "", parseInt(ho.offsetHeight/0.75));							

						}
    	  					
    	  			}
    	  			if(footer) 			
    	  			{
    	  				var fo = document.getElementById(footer);
    	  				fo.style.fontSize=window.getComputedStyle(fo,null).fontSize;
    	  				if(fo){
    	  					//uexWindow.openSlibing("2", "2", "head.html", fo.outerHTML, "", fo.offsetHeight);
    	  					uexWindow.openSlibing("2", "2", "head.html", fo.outerHTML, "", parseInt(fo.offsetHeight/0.75));
						}
    	  					
    	  			}
					document.body.style.fontSize=window.getComputedStyle(document.body,null).fontSize;
      				//},1000);
      			}
      			break;
      			case 1:
      				var ao=document.getElementById(header);
      				if(ao)
      				{
      					uexWindow.showSlibing("1");
      				}
      				break;
      			case 2:
      				var bo=document.getElementById(footer);
      				if(bo)
      				{
       					uexWindow.showSlibing("2");
      				}
      				break;
          	}
      	};
      	window.uexOnshow=function(type)
      	{
      		switch(type)
      		{
				
				case 1:
					var ao=document.getElementById(header);
					if(ao)
					{
						ao.style.display="none";
					}
					if(!footer && cb)
						cb();
					break;
				case 2:
					var bo=document.getElementById(footer);
					if(bo)
					{
						bo.style.display="none";
					}
					if(cb)
						cb();
					break;
      		}
      	};
      	if(run)
		{
      		window.uexOnload(0);
		}
    }
function zy_parse() {
	var params = {};
    var loc = String(document.location);
    if (loc.indexOf("?") > 0) 
        loc = loc.substr(loc.indexOf('?') + 1);
    else 
        loc = uexWindow.getUrlQuery();
    var pieces = loc.split('&');
    params.keys = [];
    for (var i = 0; i < pieces.length; i += 1) {
        var keyVal = pieces[i].split('=');
        params[keyVal[0]] = decodeURIComponent(keyVal[1]);
        params.keys.push(keyVal[0]);
    }
    return params;
}
/*function zy_parse() {
	var params = {};
	var loc = String(document.location);
	var pieces = loc.substr(loc.indexOf('?') + 1).split('&');
	params.keys = [];
	for (var i = 0; i < pieces.length; i += 1) {
		var keyVal = pieces[i].split('=');
		params[keyVal[0]] = decodeURIComponent(keyVal[1]);
		params.keys.push(keyVal[0]);
	}
	return params;
}*/

function zy_init()
{
	setTimeout(function(){document.body.style.fontSize=window.getComputedStyle(document.body,null).fontSize},1500);
}