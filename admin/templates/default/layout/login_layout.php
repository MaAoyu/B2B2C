<?php defined('InShopNC') or exit('Access Invalid!');?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET;?>">
<title><?php echo $output['html_title'];?></title>
<link href="<?php echo ADMIN_SITE_URL;?>/resource/font/css/font-awesome.min.css" rel="stylesheet">
<link href="<?php echo ADMIN_TEMPLATES_URL;?>/css/login.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="<?php echo RESOURCE_SITE_URL;?>/js/jquery.js"></script>
<script type="text/javascript" src="<?php echo RESOURCE_SITE_URL;?>/js/common.js"></script>
<script type="text/javascript" src="<?php echo RESOURCE_SITE_URL;?>/js/jquery.tscookie.js"></script>
<script type="text/javascript" src="<?php echo RESOURCE_SITE_URL;?>/js/jquery.validation.min.js"></script>
<script src="<?php echo ADMIN_SITE_URL;?>/resource/js/jquery.supersized.min.js" ></script>
<script src="<?php echo ADMIN_SITE_URL;?>/resource/js/jquery.progressBar.js" type="text/javascript"></script>
</head>
<body>
<?php 
require_once($tpl_file);
?>

<script>
$(function(){
        $.supersized({

        // 功能
        slide_interval     : 4000,    
        transition         : 1,    
        transition_speed   : 1000,    
        performance        : 1,    

        // 大小和位置
        min_width          : 0,    
        min_height         : 0,    
        vertical_center    : 1,    
        horizontal_center  : 1,    
        fit_always         : 0,    
        fit_portrait       : 1,    
        fit_landscape      : 0,    

        // 组件
        slide_links        : 'blank',    
        slides             : [    
                                 {image : '<?php echo ADMIN_TEMPLATES_URL;?>/images/login/1.jpg'},
                                 {image : '<?php echo ADMIN_TEMPLATES_URL;?>/images/login/2.jpg'},
                                 {image : '<?php echo ADMIN_TEMPLATES_URL;?>/images/login/3.jpg'},
				 {image : '<?php echo ADMIN_TEMPLATES_URL;?>/images/login/4.jpg'},
				 {image : '<?php echo ADMIN_TEMPLATES_URL;?>/images/login/5.jpg'}
                       ]

    });
	//显示隐藏验证码
    $("#hide").click(function(){
        $(".code").fadeOut("slow");
    });
    $("#captcha").focus(function(){
        $(".code").fadeIn("fast");
    });
    //跳出框架在主窗口登录
   if(top.location!=this.location)	top.location=this.location;
    $('#user_name').focus();
    if ($.browser.msie && ($.browser.version=="6.0" || $.browser.version=="7.0")){
        window.location.href='<?php echo ADMIN_TEMPLATES_URL;?>/ie6update.html';
    }
    $("#captcha").nc_placeholder();
	//动画登录
    $('.input-button').click(function(e){
            $('.input-username,dot-left').addClass('animated fadeOutRight')
            $('.input-password-box,dot-right').addClass('animated fadeOutLeft')
            $('.input-button').addClass('animated fadeOutUp')
            setTimeout(function () {
                      $('.avatar').addClass('avatar-top');
                      $('.submit').hide();
                      $('.submit2').html('<div class="progress"><div class="progress-bar progress-bar-success" aria-valuetransitiongoal="100"></div></div>');
                      $('.progress .progress-bar').progressbar({
                          done : function() {$('#form_login').submit();}
                      }); 
              },
          300);

          });

    // 回车提交表单
    $('#form_login').keydown(function(event){
        if (event.keyCode == 13) {
            $('.input-button').click();
        }
    });
});

</script>
</body>
</html>
