$(function(){
    var memberHtml = '<a class="btn mr5" href="'+SiteUrl+'/shop/api.php?act=toqq&mobile=mobile">QQ登陆</a><a class="btn mr5" href="'+WapSiteUrl+'/tmpl/member/register.html">注册</a>';
    var act = GetQueryString("act");
    if(act && act == "member"){
        memberHtml = '<a class="btn mr5" id="logoutbtn" href="javascript:void(0);">注销</a>';
    }
    var tmpl = '<div class="footer">'
        +'<div class="footer-top">'
            +'<div class="footer-tleft">'+ memberHtml +'</div>'
            +'<a href="javascript:void(0);"class="gotop">'
                +'<span class="gotop-icon"></span>'
                +'<p>回顶部</p>'
            +'</a>'
        +'</div>'
         +'<div class="footer-content">'
            +'<p class="link">'
			+'<a href="'+WapSiteUrl+'/shop.html" class="standard">所有店铺</a>'
            +'<a href="'+WapSiteUrl+'/tmpl/article_list.html?ac_id=2" class="standard">帮助中心</a>'
            +'<a href="'+WapSiteUrl+'/tmpl/article_list.html?ac_id=7">关于我们</a>'
            +'</p>'
            /*+'<p class="copyright">'
                +'版权所有 2014-2015 © www.33hao.com'
            +'</p>'*/
        +'</div>'
    +'</div>';
	var tmpl2 = '<div id="bottom">'
		+'<div style=" height:40px;">'
  +'<div id="nav-tab" style="bottom:-40px;">'
            +'<div id="nav-tab-btn"><i class="fa fa-chevron-down"></i></div>'
            +'<div class="clearfix tab-line nav">'
      +'<div class="tab-line-item" style="width:25%;" ><a href="'+WapSiteUrl+'"><i class="fa fa-home"></i><br>首页</a></div>'
      +'<div class="tab-line-item" style="width:25%;" ><a href="'+WapSiteUrl+'/tmpl/product_first_categroy.html"><i class="fa fa-th-list"></i><br>分类</a></div>'
      <!--+'<div class="tab-line-item" style="width:22%;line-height:40px;padding-top:5px;" ><i style="font-size:30px;" class="fa fa-chevron-circle-down"></i><br></div>'-->
      +'<div class="tab-line-item" style="width:25%;position: relative;" ><a href="'+WapSiteUrl+'/tmpl/cart_list.html"><i class="fa fa-shopping-cart"></i><br>购物车</a></div>'
      +'<div class="tab-line-item" style="width:25%;" ><a href="'+WapSiteUrl+'/tmpl/member/member.html?act=member"><i class="fa fa-user"></i><br>个人中心</a></div>'
    +'</div>'
   +'</div>'
+'</div>'
		+'<div style="z-index: 10000; border-radius: 3px; position: fixed; background: none repeat scroll 0% 0% rgb(255, 255, 255); display: none;" id="myAlert" class="modal hide fade">'
  +'<div style="text-align: center;padding: 15px 0 0;" class="title"></div>'
  +'<div style="min-height: 40px;padding: 15px;" class="modal-body"></div>'
  +'<div style="padding:3px;height: 35px;line-height: 35px;" class="alert-footer">'
  +'<a style="padding-top: 4px;border-top: 1px solid #ddd;display: block;float: left;width: 50%;text-align: center;border-right: 1px solid #ddd;margin-right: -1px;" class="confirm" href="javascript:;">Save changes</a><a aria-hidden="true" data-dismiss="modal" class="cancel" style="padding-top: 4px;border-top: 1px solid #ddd;display: block;float: left;width: 50%;text-align: center;" href="javascript:;">关闭</a></div>'
+'</div>'
		+'<div style="display:none;" class="tips"><i class="fa fa-info-circle fa-lg"></i><span style="margin-left:5px" class="tips_text"></span></div>'
		+'<div class="bgbg" id="bgbg" style="display: none;"></div>'
        +'</div>'
	+'</div>';
	var render = template.compile(tmpl);
	var html = render();
	$("#footer").html(html+tmpl2);
    //回到顶部
    $(".gotop").click(function (){
        $(window).scrollTop(0);
    });
    var key = getcookie('key');
	$('#logoutbtn').click(function(){
		var username = getcookie('username');
		var key = getcookie('key');
		var client = 'wap';
		$.ajax({
			type:'get',
			url:ApiUrl+'/index.php?act=logout',
			data:{username:username,key:key,client:client},
			success:function(result){
				if(result){
					delCookie('username');
					delCookie('key');
					location.href = WapSiteUrl+'/tmpl/member/login.html';
				}
			}
		});
	});	
	
	var referurl = document.referrer;//上级网址
	$("input[name=referurl]").val(referurl);
	$.sValid.init({
        rules:{
            username:"required",
            userpwd:"required"
        },
        messages:{
            username:"用户名必须填写！",
            userpwd:"密码必填!"
        },
        callback:function (eId,eMsg,eRules){
            if(eId.length >0){
                var errorHtml = "";
                $.map(eMsg,function (idx,item){
                    errorHtml += "<p>"+idx+"</p>";
                });
                $(".error-tips").html(errorHtml).show();
            }else{
                 $(".error-tips").html("").hide();
            }
        }  
    });
	$('#loginbtn').click(function(){//会员登陆
		var username = $('#username').val();
		var pwd = $('#userpwd').val();
		var client = 'wap';
		if($.sValid()){
	          $.ajax({
				type:'post',
				url:ApiUrl+"/index.php?act=login",	
				data:{username:username,password:pwd,client:client},
				dataType:'json',
				success:function(result){
					if(!result.datas.error){
						if(typeof(result.datas.key)=='undefined'){
							return false;
						}else{
							addcookie('username',result.datas.username);
							addcookie('key',result.datas.key);
							location.href = referurl;
						}
						$(".error-tips").hide();
					}else{
						$(".error-tips").html(result.datas.error).show();
					}
				}
			 });  
        }
	});
});