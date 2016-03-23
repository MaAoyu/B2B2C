<?php
/**
 *
 * 好商城V3 instller
 *
 *
 */
// 设置最大执行时间
set_time_limit(0);
define('InShopNC',true);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
@ini_set ('memory_limit', '512M');
@set_magic_quotes_runtime(0);
$config_file = '../../data/config/config.ini.php';
$setting_file = '../../data/cache/setting.php';
$config = require($config_file);
$setting = require($setting_file);

$site_url = $config['admin_site_url'];
$version = $config['version'];
$dbcharset = $config['db']['1']['dbcharset'];
$dbserver = $config['db']['1']['dbhost'];
$dbserver_port = $config['db']['1']['dbport'];
$dbname = $config['db']['1']['dbname'];
$db_pre = $config['tablepre'];
$dbuser = $config['db']['1']['dbuser'];
$dbpasswd = $config['db']['1']['dbpwd'];
define('DBCHARSET',$dbcharset);
define('SiteUrl',$site_url);

$db = new dbstuff();
$dbcharset = 'utf8';
$dbserver = $dbserver.":".$dbserver_port;
$db->connect($dbserver, $dbuser, $dbpasswd, $dbname, $dbcharset);
$tablepre = $db_pre;
$current = $_GET['act'];

//新增表和字段
function update_db() {
	$sqlfile = 'utf8.sql';
	global $tablepre, $db,$config;
	$sql = file_get_contents($sqlfile);
	$sql = str_replace("\r\n", "\n", $sql);
	runquery($sql);
	//$db->query('update '.$tablepre.'goods set is_own_shop = 1 where store_id = '.$config['default_store_id']);
	//$db->query('update '.$tablepre.'goods_common set is_own_shop = 1 where store_id = '.$config['default_store_id']);
	//$db->query('update '.$tablepre.'store set is_own_shop = 1 where store_id = '.$config['default_store_id']);
	//$db->query('update '.$tablepre.'store set bind_all_gc = 1 where store_id = '.$config['default_store_id']);
}

//execute sql
//执行sql
function runquery($sql) {
	global $tablepre, $db;

	if(!isset($sql) || empty($sql)) return;

	$sql = str_replace("\r", "\n", str_replace('33hao_', $tablepre, $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0].$query[1] == '--') ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $key => $query) {
		$query = trim($query);
		if($query) {
			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$line = explode('`',$query);
				$data_name = $line[1];
				showjsmessage('数据表'.' '.$data_name.' ... '.'建立成功',$key);
				$db->query($query);
			} elseif(substr($query, 0, 11) == 'ALTER TABLE') {
				$db->query($query);
			} else {
				$db->query($query);
			}
		}
	}
}
//JS信息
function showjsmessage($message,$n=1) {
	echo 'setTimeout("showmessage(\''.addslashes($message).' \')",'.($n*150).');';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>好商城V3插件/升级包</title>
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<link href="install.css" rel="stylesheet" type="text/css">
<meta content="ShopNC" name="Copyright" />
</head>
	<div class="header">
      <div class="layout">
        <div class="title">
          <h5>好商城V3提供升级包</h5>
          <h2>好商城V3-b11升级包</h2>
        </div>
        <div class="version">版本: 2015.06.23</div>
      </div>
	</div>
	<table class="menu" align="center">
			<tr class="menu_tr">
				<td align="center" <?php if($current == '') echo 'class="current"'; ?>>安装说明</td>
				<td align="center" <?php if($current == '1') echo 'class="current"'; ?>>新增表和字段</td>
				<td align="center" <?php if($current == '3') echo 'class="current"'; ?>>安装完成</td>
			</tr>
	</table>
				<div class="main">
				<div class="text-box" id="text-box">
				<?php if ($current == ''){ ?>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;如果你的版本里未安装www.33hao.com模块！你只需简单一步就可以安装好好商城V3-B11升级包了哦！感谢你对好商城V3的支持！<span style="color: #FF0000;">如因安装造成您原有二次开发功能的错误或丢失，www.33hao.com不予承担任何责任及损失，请您慎重操作。
				</span><br /><br />
				建议在升级过程中暂时关闭网站。进行下面操作前请确认已经完成备份，程序将自动修改数据库，点击按钮开始执行。<br />
				<br />
				<span style="color: #FF0000;">重要说明：</span><br />
			  <div>
			  	1、&nbsp;&nbsp;其他文件直接按文件名存放替换就可以了<br />
			  	2、&nbsp;&nbsp;本程序由www.33hao.com开发！转载请保留版权<br />
			  	3、&nbsp;&nbsp;好商城v3官方论坛：http://www.33hao.com<br />

			  	<br /><br />
			  </div>
				<div class="btnbox marginbot">
					<form method="get" action="index.php">
					<input type="hidden" name="act" value="1">
					<input type="hidden" name="go" value="3">
					<input type="submit" value="确认安装" style="padding: 2px">
					</form>
				</div>
			  <?php } elseif($current == '1') { ?>
			  <div class="btnbox"><textarea name="notice" class="shop-tex"  readonly="readonly" id="notice"></textarea></div>
					<script type="text/javascript">
					function showmessage(message) {
						document.getElementById('notice').value += message + "\r\n";
					}
					<?php update_db();?>
					</script>
			  <?php } else {
			    $config_contents = @file_get_contents($config_file);
			  	?>
			  安装完成，进入<a href="<?php echo SiteUrl;?>" target="_blank">系统后台</a>更新缓存后即可使用。<span style="color:red">升级完后，记得删除本文件夹。</span>
			  <?php } ?>
				</div>
				</div>
<div class="footer">
  <h5><a href="http://www.33hao.com" target="_blank">好商城V3提供</a></h6>
</div>
<script type="text/javascript">
<?php if ($_GET['go'] == '3'){ ?>
	window.setTimeout("javascript:location.href='index.php?act=3'", 2900);
<?php } ?>
</script>
</body>
</html>
<?php
//---------------------数据库操作类
class dbstuff {
	var $querynum = 0;
	var $link;
	var $histories;
	var $time;
	var $tablepre;

	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset, $pconnect = 0, $tablepre='', $time = 0) {
		$this->time = $time;
		$this->tablepre = $tablepre;
		if($pconnect) {
			if(!$this->link = mysql_pconnect($dbhost, $dbuser, $dbpw)) {
				$this->halt('Can not connect to MySQL server');
			}
		} else {
			if(!$this->link = mysql_connect($dbhost, $dbuser, $dbpw, 1)) {
				$this->halt('Can not connect to MySQL server');
			}
		}

		if($this->version() > '4.1') {
			if($dbcharset) {
				mysql_query("SET character_set_connection=".$dbcharset.", character_set_results=".$dbcharset.", character_set_client=binary", $this->link);
			}

			if($this->version() > '5.0.1') {
				mysql_query("SET sql_mode=''", $this->link);
			}
		}

		if($dbname) {
			mysql_select_db($dbname, $this->link);
		}

	}

	function fetch_array($query, $result_type = MYSQL_ASSOC) {
		return mysql_fetch_array($query, $result_type);
	}

	function result_first($sql, &$data) {
		$query = $this->query($sql);
		$data = $this->result($query, 0);
	}

	function fetch_first($sql, &$arr) {
		$query = $this->query($sql);
		$arr = $this->fetch_array($query);
	}

	function fetch_all($sql, &$arr) {
		$query = $this->query($sql);
		while($data = $this->fetch_array($query)) {
			$arr[] = $data;
		}
	}

	function query($sql, $type = '', $cachetime = FALSE) {
		$func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ? 'mysql_unbuffered_query' : 'mysql_query';
		if(!($query = $func($sql, $this->link)) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $sql);
		}
		$this->querynum++;
		$this->histories[] = $sql;
		return $query;
	}

	function affected_rows() {
		return mysql_affected_rows($this->link);
	}

	function error() {
		return (($this->link) ? mysql_error($this->link) : mysql_error());
	}

	function errno() {
		return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
	}

	function result($query, $row) {
		$query = @mysql_result($query, $row);
		return $query;
	}

	function num_rows($query) {
		$query = mysql_num_rows($query);
		return $query;
	}

	function num_fields($query) {
		return mysql_num_fields($query);
	}

	function free_result($query) {
		return mysql_free_result($query);
	}

	function insert_id() {
		return ($id = mysql_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		$query = mysql_fetch_row($query);
		return $query;
	}

	function fetch_fields($query) {
		return mysql_fetch_field($query);
	}

	function version() {
		return mysql_get_server_info($this->link);
	}

	function close() {
		return mysql_close($this->link);
	}

	function halt($message = '', $sql = '') {
		//		echo mysql_error();echo "<br />";
	}
}
//----------------------数据库操作类 end
?>