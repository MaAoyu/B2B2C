/* 多级选择相关函数，如地区选择，分类选择
 * common_select
 */

/* 地区选择函数 */
function regionInit(divId){
	var area_id = 0;	
	getArea(function(){
		if(typeof(nc_a[area_id]) == 'object' && nc_a[area_id].length > 0){//数组存在
			var area_select = $("#" + divId + " > select");//选择要初始化的对象
			areaInit(area_select,area_id);
		}
		$("#" + divId + " > select").change(regionChange); // select的onchange事件
		$("#" + divId + " > input:button[class='edit_region']").click(regionEdit); // 编辑按钮的onclick事件
	});
}
function areaInit(area_select,area_id){//初始化地区
	getArea(function(){
		if(typeof(area_select) == 'object' && nc_a[area_id].length > 0){
			var areas = new Array();
			areas = nc_a[area_id];
			$(area_select).append("<option>-请选择-</option>");
			for (i = 0; i <areas.length; i++){
				$(area_select).append("<option value='" + areas[i][0] + "'>" + areas[i][1] + "</option>");
			}
		}
	});
}
if(typeof(regionChange) != 'function'){//检测是否已经被定义过，防止重写
	function regionChange(){
	    // 删除后面的select
	    $(this).nextAll("select").remove();
	    // 计算当前选中到id和拼起来的name
	    var selects = $(this).siblings("select").andSelf();
	    var id = '';
	    var names = new Array();
	    for (i = 0; i < selects.length; i++){
	        sel = selects[i];
	        if (sel.value > 0){
	            id = sel.value;
	            name = sel.options[sel.selectedIndex].text;
	            names.push(name);
	        }
	    }
	    $(".area_ids").val(id);
	    $(".area_name").val(name);
	    $(".area_names").val(names.join("\t"));
	    
	    if (this.value > 0){//下级地区
	        var area_id = this.value;
	        if(typeof(nc_a[area_id]) == 'object' && nc_a[area_id].length > 0){//数组存在
	        	$("<select></select>").change(regionChange).insertAfter(this);
	        	areaInit($(this).next("select"),area_id);//初始化地区
	        }
	    }
	}
}

function regionEdit()
{
    $(this).siblings("select").show();
    $(this).siblings("span").andSelf().hide();
}

/* 商品分类选择函数 */
function gcategoryInit(divId)
{
    $("#" + divId + " > select").get(0).onchange = gcategoryChange; // select的onchange事件
    window.onerror = function(){return true;}; //屏蔽jquery报错
    $("#" + divId + " .edit_gcategory").click(gcategoryEdit); // 编辑按钮的onclick事件
}

function gcategoryChange()
{
    // 删除后面的select
    $(this).nextAll("select").remove();

    // 计算当前选中到id和拼起来的name
    var selects = $(this).siblings("select").andSelf();
    var id = 0;
    var names = new Array();
    for (i = 0; i < selects.length; i++)
    {
        sel = selects[i];
        if (sel.value > 0)
        {
            id = sel.value;
            name = sel.options[sel.selectedIndex].text;
            names.push(name);
        }
    }
    $(this).parent().find(".mls_id").val(id);
    $(this).parent().find(".mls_name").val(name);
    $(this).parent().find(".mls_names").val(names.join("\t"));

    // ajax请求下级分类
    if (this.value > 0)
    {
        var _self = this;
        var url = SITEURL + '/index.php?act=index&op=josn_class&callback=?';
        $.getJSON(url, {'gc_id':this.value}, function(data){
            if (data)
            {
                if (data.length > 0)
                {
                    $("<select class='class-select'><option>-请选择-</option></select>").change(gcategoryChange).insertAfter(_self);
                    var data  = data;
                    for (i = 0; i < data.length; i++)
                    {
                        $(_self).next("select").append("<option data-explain='" + data[i].commis_rate + "' value='" + data[i].gc_id + "'>" + data[i].gc_name + "</option>");
                    }
                }
            }
        });
    }
}

function gcategoryEdit()
{
    $(this).siblings("select").show();
    $(this).siblings("span").andSelf().remove();
}
if(typeof(jQuery.validator.addMethod) == 'function'){//添加自动检测是否是最后一级地区
	jQuery.validator.addMethod("checkarea", function(value, element) {
		return this.optional(element) || (typeof(nc_a[value]) == 'undefined');//当数组不存在时确定选到最后
	}, "请选择所在地区");
} 
function getArea(callback){
	if(typeof(nc_a) == 'undefined'){//加载地区数据
		var area_scripts_src = '';
		area_scripts_src = $("script[src*='jquery.js']").attr("src");//取JS目录的地址
		area_scripts_src = area_scripts_src.replace('jquery.js', 'area_array.js');
		$.getScript(area_scripts_src,function(){
				callback();
				return true;
		});
	} else {
		callback();
	}
}
//显示一级分类下拉框
function show_gc_1(depth,gc_json){
	var html = '<select name="search_gc[]" id="search_gc_0" nc_type="search_gc" class="querySelect">';;
	html += ('<option value="0">请选择...</option>');
	if(gc_json){
		for(var i in gc_json){
			if(gc_json[i].depth == 1){
				html += ('<option value="'+gc_json[i].gc_id+'">'+gc_json[i].gc_name+'</option>');
			}
		}
	}
	html += '</select>';
	$("#searchgc_td").html(html);
}
//显示子分类下拉框
function show_gc_2(chooseid,gc_json){
	if(gc_json && chooseid > 0){
		var childid = gc_json[chooseid].child;
		if(childid){
			var html = '<select name="search_gc[]" id="search_gc_'+gc_json[chooseid].depth+'" nc_type="search_gc" class="querySelect">';;
			html += ('<option value="0">请选择...</option>');
			var childid_arr = childid.split(",");
			if(childid_arr){
				for(var i in childid_arr){
					html += ('<option value="'+gc_json[childid_arr[i]].gc_id+'">'+gc_json[childid_arr[i]].gc_name+'</option>');
				}
			}
			html += '</select>';
			$("#searchgc_td").append(html);
		}
	}
}
//初始化商品分类select
//chooseid_arr为已选gc_id的json数组
function init_gcselect(chooseid_json,gc_json){
	show_gc_1(1,gc_json);
	if(chooseid_json){
		for(var i in chooseid_json){
			show_gc_2(chooseid_json[i],gc_json);
			$('#search_gc_'+i).val(chooseid_json[i]);
			$('#choose_gcid').val(chooseid_json[i]);
		}
	}
	//商品分类select绑定事件
	$("[nc_type='search_gc']").live('change',function(){
        $(this).nextAll("[nc_type='search_gc']").remove();
        var chooseid = $(this).val();
		if(chooseid > 0){
			$("#choose_gcid").val(chooseid);
			show_gc_2(chooseid,gc_json);
		} else {
			chooseid = $(this).prev().val();
			$("#choose_gcid").val(chooseid);
		}
    });
}