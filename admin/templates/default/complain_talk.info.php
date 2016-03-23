<script type="text/javascript">
$(document).ready(function(){
    get_complain_talk();
    $("#btn_publish").click(function(){
        if($("#complain_talk").val()=='') {
            alert("<?php echo $lang['talk_null'];?>");
        }
        else {
            publish_complain_talk();
        }
    });
    $("#btn_refresh").click(function(){
        get_complain_talk();
    });
});
function get_complain_talk() {
    $("#div_talk").empty();
    $.ajax({
        type:'POST',
        url:'index.php?act=complain&op=get_complain_talk',
        cache:false,
        data:"complain_id=<?php echo $output['complain_info']['complain_id'];?>",
        dataType:'json',
        error:function(){
                $("#div_talk").append("<p class='admin'>"+"<?php echo $lang['talk_none'];?>"+"</p>");
        },
        success:function(talk_list){
            if(talk_list.length >= 1) {
                for(var i = 0; i < talk_list.length; i++)
                {
                    var link = "<p class='"+talk_list[i].css+"'>"+talk_list[i].talk+"</p>";
                    $("#div_talk").append(link);
                }
            }
            else {
                $("#div_talk").append("<p class='admin'>"+"<?php echo $lang['talk_none'];?>"+"</p>");
            }
        }
	});
}
function publish_complain_talk() {
        $.ajax({
            type:'POST',
                url:'index.php?act=complain&op=publish_complain_talk',
                cache:false,
                data:"complain_id=<?php echo $output['complain_info']['complain_id'];?>&complain_talk="+encodeURIComponent($("#complain_talk").val()),
                dataType:'json',
                error:function(){
                    alert("<?php echo $lang['talk_send_fail'];?>");
                },
                    success:function(talk_list){
                        if(talk_list == 'success') {
                            $("#complain_talk").val('');
                            get_complain_talk();
                            alert("<?php echo $lang['talk_send_success'];?>");
                        }
                        else {
                            alert("<?php echo $lang['talk_send_fail'];?>");
                        }
                    }
        });
}

function forbit_talk(talk_id) {
        $.ajax({
            type:'POST',
                url:'index.php?act=complain&op=forbit_talk',
                cache:false,
                data:"talk_id="+talk_id,
                dataType:'json',
                error:function(){
                    alert("<?php echo $lang['talk_forbit_fail'];?>");
                },
                    success:function(talk_list){
                        if(talk_list == 'success') {
                            get_complain_talk();
                            alert("<?php echo $lang['talk_forbit_success'];?>");
                        }
                        else {
                            alert("<?php echo $lang['talk_forbit_fail'];?>");
                        }
                    }
        });
}
</script>

<table class="table tb-type2 order mtw">
  <thead class="thead">
    <tr class="space">
      <th><?php echo $lang['talk_detail'];?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th><?php echo $lang['talk_list'];?></th>
    </tr>
    <tr class="noborder">
      <td class="complain-content"><div id="div_talk" class="div_talk"> </div></td>
    </tr>
    <?php if(intval($output['complain_info']['complain_state']) !== 99) { ?>
    <tr>
      <th><?php echo $lang['talk_send'];?></th>
    </tr>
    <tr class="noborder">
      <td>
          <textarea id="complain_talk" class="tarea"></textarea>
        </td>
    </tr>
    <tr>
      <td><a href="JavaScript:void(0);" id="btn_refresh" class="btn"><span><?php echo $lang['talk_refresh'];?></span></a><a href="JavaScript:void(0);" id="btn_publish" class="btn"><span><?php echo $lang['talk_send'];?></span></a></td>
    </tr>
    <?php } ?>
  </tbody>
</table>
