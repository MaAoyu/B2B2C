<?php defined('InShopNC') or exit('Access Invalid!');?>

<div class="page">
  <div class="fixed-bar">
    <div class="item-title">
      <h3><?php echo $lang['nc_limit_manage'];?></h3>
      <?php echo $output['top_link'];?>
    </div>
  </div>
  <div class="fixed-empty"></div>
  <form method="post" id='form_admin'>
    <input type="hidden" name="form_submit" value="ok" />
    <table class="table tb-type2">
      <thead>
        <tr class="space">
          <th colspan="15" class="nobg"><?php echo $lang['nc_list'];?></th>
        </tr>
        <tr class="thead">
          <th><input type="checkbox" class="checkall" id="checkallBottom" name="chkVal"></th>
          <th><?php echo $lang['admin_index_username'];?></th>
          <th class="align-center"><?php echo $lang['admin_index_last_login'];?></th>
          <th class="align-center"><?php echo $lang['admin_index_login_times'];?></th>
          <th class="align-center"><?php echo $lang['gadmin_name'];?></th>
          <th class="align-center"><?php echo $lang['nc_handle'];?></th>
        </tr>
      </thead>
      <tbody>
        <?php if(!empty($output['admin_list']) && is_array($output['admin_list'])){ ?>
        <?php foreach($output['admin_list'] as $k => $v){ ?>
        <tr class="hover">
          <td class="w24"><?php if ($v['admin_is_super'] != 1){?>
            <input type="checkbox" name="del_id[]" value="<?php echo $v['admin_id']; ?>" class="checkitem" onclick="javascript:chkRow(this);">
            <?php }else { ?>
            <input name="del_id[]" type="checkbox" value="<?php echo $v['admin_id']; ?>" disabled="disabled">
            <?php }?></td>
          <td><?php echo $v['admin_name'];?></td>
          <td class="align-center"><?php echo $v['admin_login_time'] ? date('Y-m-d H:i:s',$v['admin_login_time']) : $lang['admin_index_login_null']; ?></td>
          <td class="align-center"><?php echo $v['admin_login_num']; ?></td>
          <td class="align-center"><?php echo $v['gname']; ?></td>
          <td class="w150 align-center"><?php if($v['admin_is_super']){?>
            <?php echo $lang['admin_index_sys_admin_no'];?>
            <?php }else{?>
            <a href="javascript:void(0)" onclick="if(confirm('<?php echo $lang['nc_ensure_del'];?>')){location.href='index.php?act=admin&op=admin_del&admin_id=<?php echo $v['admin_id']; ?>'}"><?php echo $lang['admin_index_del_admin'];?></a> | <a href="index.php?act=admin&op=admin_edit&admin_id=<?php echo $v['admin_id']; ?>"><?php echo $lang['nc_edit'];?></a>
            <?php }?></td>
        </tr>
        <?php } ?>
        <?php }else { ?>
        <tr class="no_data">
          <td colspan="10"><?php echo $lang['nc_no_record'];?></td>
        </tr>
        <?php } ?>
      </tbody>
      <tfoot>
        <?php if(!empty($output['admin_list']) && is_array($output['admin_list'])){ ?>
        <tr class="tfoot">
          <td><input type="checkbox" class="checkall" id="checkallBottom" name="chkVal"></td>
          <td colspan="16"><label for="checkallBottom"><?php echo $lang['nc_select_all']; ?></label>
            &nbsp;&nbsp;<a href="JavaScript:void(0);" class="btn" onclick="if(confirm('<?php echo $lang['nc_ensure_del'];?>')){$('#form_admin').submit();}"><span><?php echo $lang['nc_del'];?></span></a>
            <div class="pagination"> <?php echo $output['page'];?> </div></td>
        </tr>
        <?php } ?>
      </tfoot>
    </table>
  </form>
</div>
