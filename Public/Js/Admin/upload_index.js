var arrTemp = [];
$("#addPart").click(function(){
	var allPart = $("#allPart"),partNum,partId; 
	if(allPart.length){
		partId = parseInt($("#allPart .mDataCon").eq(length-1).attr("id").split("_")[1])+1;
		var htm ='<div class="add_new mDataCon" id="part_'+partId+'">'+
					'<a class="js_delete" href="javascript:;">delete</a>'+
				    '<p class="clearfix add_new_p">'+
				        '<label>案例空间：</label>'+
				        '<select name="case_space_'+partId+'_space">'+
				            '<option value="客厅">客厅</option>'+
				            '<option value="厨房">厨房</option>'+
				            '<option value="卧室">卧室</option>'+
				            '<option value="阳台">阳台</option>'+
				        '</select>'+
				        '<label>案例面积： </label>'+
				        '<input name="case_space_'+partId+'_area" type="text" />'+
				        '<span>平米</span>'+
				    '</p>'+
				    '<div class="clearfix img_div">'+
				        '<label class="left">装修前图片：</label>'+
				        '<ul class="left img_ul">'+
				            '<li>'+
				                '<ul class="mDataList" ></ul>'+
				                '<div class="uploadImg"><input class="uploadBtn" type="button" value="添加图片"/></div>'+
				            '</li>'+
				        '</ul>'+
				        '<span class="left">最多三张图片</span>'+
				    '</div>'+
				    '<div class="clearfix img_div">'+
				        '<label class="left">装修后图片：</label>'+
				        '<ul class="left img_ul">'+
				            '<li>'+
				                '<ul class="mDataList"></ul>'+
				                '<div class="uploadImg"><input class="uploadBtn" type="button" value="添加图片"/></div>'+
				            '</li>'+
				        '</ul>'+
				        '<span class="left">最多三张图片</span>'+
				    '</div>'+
				     '<p>'+
				        '<label for="description">案例描述:</label>'+
				        '<textarea name="case_space_'+partId+'_description" class="required">'+
				        '</textarea>'+
				    '</p>'+
				'</div>';
	}
    $("#allPart").append(htm); 
    load(partId,$('#allPart .uploadBtn'), $('#allPart .mDataList'));
});

$("body").on("click",".js_delete",function(){
	var $target = $(this).parents(".add_new"),
		$swf = $target.find('.mDataList');
	var isInArr = function(item, arr){
		if( arr.length > 0 ){
			for( var i = 0; i < arr.length; i++ ){
				if( arr[i] == item ){
					return i;
					break;
				}
			}
			return -1;
		}else{
			return -1;
		}
	};
	var removeInArr = function(item, arr){
		var index = isInArr(item, arr);
	    if (index > -1) {
	        arr.splice(index, 1);
	    	return arr;
	    }else{
	    	return undefined;
	    }
	};
	$target.remove();
	$swf.each(function(){
		var id = $(this).attr('id').split('_')[3];
		if(isInArr(id,arrTemp ) != -1){
			removeInArr(id,arrTemp);
			delete pic_upload[id];
		}
	});
})

/* 上传图片 */
var pic_upload = {};
var handler={
	sourceObj:null,
	targetObj:null,
	sourceIndex:0,
	targetIndex:0,
	limitFlag:0,
	toBitSize:function(num, to_round){
		var type = new Array( "B", "KB", "MB", "GB", "TB", "B" );
		var j = 0;
		while( num >= 1024 ){
			if( j >= 5 ){
				return num + type[j];
			}
			if(to_round != 0){
				num = Math.round(num / 1024, to_round);
			}else{
				num = num/1024;
			}
			j++;
		}
		return num + type[j];
	},
	drag:function(liObj){
			var mId = $(liObj).attr('id');
			mId0 = mId.split("_");
			mIdd = mId0[1];
			liObj.draggable({
				zIndex: 100,
				revert:true,
				containment:"#case_space_list"+mIdd,
				scroll:false,
				cursor: 'move',
				delay: 0,
				opacity: 0.5,
				cancel:"div.pic,div.upload_img,img,div.label,div.options,label",
				start:function(e,ui){
					e.stopPropagation();
					handler.sourceObj = e.srcElement || e.target;
					$(handler.sourceObj).addClass('active').siblings().removeClass('active');
					handler.sourceIndex = $(handler.sourceObj).index();
				}
			});

			liObj.droppable({
				drop: function(e) {
					e.stopPropagation();
					$(handler.sourceObj).removeClass('active');
					var height = liObj.height();
					var index = handler.targetObj.index();
					$(handler.sourceObj).css({top:"0px",left:"0px","z-index":0});
					if ( handler.sourceIndex < index ){
						$(handler.sourceObj).insertAfter(handler.targetObj);
					} else {
						$(handler.sourceObj).insertBefore(handler.targetObj);
					}
				},
				over:function(e){
					e.stopPropagation();
					handler.targetObj = $(this);
					handler.targetIndex = handler.targetObj.index();
				}
			});
	},
	/**
	 * 文件上传中句柄
	 */
	fileQueued : function(file){
		var mFid =file.id;
		mFid0 = mFid.split("_");
		mFidd = mFid0[1];
		if(mFidd == 0){
			var lim_len = 1;
		}
		if(mFidd == 1){
			var lim_len = 4;
		}
		var list = $("#case_space_list_"+mFidd).find("li");
		var len = list.length;
		if ( len >= lim_len ){
			pic_upload[mFidd].setButtonDisabled(true);
			pic_upload[mFidd].setButtonImageURL("http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtnGrey.png");
			return false;
		}
			var html = '<li id="'+file.id+'" class="clearfix">\
				            <div class="upload_img">\
					            <img src="http://static.jiaju.com/jiaju/com/images/s/loading.gif" />\
					        </div>\
					        <a href="javascript:;" class="input_delete_ico" title="删除">×</a>\
					        <input class="o_hidden" type="hidden" name="" value="" >\
				        </li>';
			$("#case_space_list_"+mFidd).append(html);
	},
	/**
	*删除上传图片
	*/			

	cancelUpload:function(file_id){
		pic_upload.cancelUpload(file_id);
	},
	/**
	 * 文件选择完成句柄
	 */
	fileDialogComplete : function(numFilesSelected, numFilesQueued) {
		try {
			this.startUpload();
			
			if(numFilesQueued>0){
				//当队列中文件数量大于5个时显示滚动条

				$('#upload_box').hide();
				$('#pic20_list').show();
				$('.boxBtn').show();
			}else{
				return ;	
			}

		} catch (ex)  {
			this.debug(ex);
		}
	},
	/**
	 * 文件开始上传句柄
	 */
	uploadStart : function(file) {
		$('#' + file.id + ' .tip_info').html('提示：文件开始上传......');
	},
	/**
	 * 文件上传完成句柄
	 */
	uploadComplete : function(file){
		this.startUpload();
		// verifyUpload();
	},
	/**
	 * 文件上传成功句柄
	 */
	uploadSuccess : function(file, serverData) {
		serverData = $.parseJSON(serverData);
		if(serverData && serverData.code==1){
			var img = $('#' + file.id).find('.upload_img img');
			img.attr('src',serverData.data.url);
			img.error(function(e){
				var e = e||window.event;
				var img=e.srcElement;
				img.src=serverData.data.url;
				img.onerror=null;//控制不要一直跳动
			})
			var fileId = $('#'+file.id);
			var numId = (file.id).split("_")[1];
			var index = fileId.parents('.img_div').index();
			var indexLi = fileId.index();
			var $liPre = fileId.prev();
			var $inputPre = $liPre.find('input');
			var idPre = (indexLi == 0 ? false : $inputPre.attr('name').split('_').pop());
			var id = fileId.parents('.mDataCon').attr('id').split('_')[1];
			if(index==2){
				var lenrth = fileId.parent(".mDataList").children("li").length-1;
				if(!indexLi){
					fileId.find("input[type=hidden]").attr("name","case_space_"+id+"_beforeimage_"+lenrth);
				}else{
					fileId.find("input[type=hidden]").attr("name","case_space_"+id+"_beforeimage_"+(parseInt(idPre)+1));
					
				}
			}else{
				var lenrth = fileId.parent(".mDataList").children("li").length-1;
				if(!indexLi){
					fileId.find("input[type=hidden]").attr("name","case_space_"+id+"_afterimage_"+lenrth);
				}else{
					fileId.find("input[type=hidden]").attr("name","case_space_"+id+"_afterimage_"+(parseInt(idPre)+1));
				}
			}
			fileId.find("input[type=hidden]").val(serverData.data.id);
		} else {
			//更改上传图标标识符状态
			alert("添加图片失败！");
		}

		var mFid =file.id;
		mFid0 = mFid.split("_");
		mFidd = mFid0[1];
		if(mFidd == 0){
			var lim_len = 1;
		}
		if(mFidd == 1){
			var lim_len = 4;
		}
		var list = $("#case_space_list_"+mFidd).find("li");

		var len = list.length;
		if ( len >= lim_len ){
			pic_upload[mFidd].setButtonDisabled(true);
			pic_upload[mFidd].setButtonImageURL("http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtnGrey.png");
		}
	},
	/**
	 * 文件上传出错句柄
	 */
	fileQueueError : function(file, code, message){
		// $('#' + file.id + ' .wait_status').addClass('error');
		switch(code){
			case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT: 
				alert('大小不超过2M，支持jpg、jpeg、png、gif格式！');
				break;
			case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE: 
				alert('大小不超过2M，支持jpg、jpeg、png、gif格式！');
				break;
			case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE: 
				alert('大小不超过2M，支持jpg、jpeg、png、gif格式！');
				break;
		}
		return false;
	},
	/**
	 * 文件上传出错句柄
	 */
	uploadError : function(file, code, message){
		switch(code){
			case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED: 
				$('.tip_info').html('上传出错，文件取消上传！');
				break;
			default:break;
		}
	},
	flashReady:function(){
		// verifyUpload();
		var list=[];
		for (var i in pic_upload){
			list[i] = $('.mDataList').eq(i).find("li").length;
			if (i == 0) {
				var _lenlimit = 1;
			} else {
				var _lenlimit = 4;
			}
			if ( list[i] >= _lenlimit ){
				pic_upload[i].setButtonDisabled(true);
				pic_upload[i].setButtonCursor(SWFUpload.CURSOR.ARROW);
				pic_upload[i].setButtonImageURL("http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtnGrey.png");
			}else{
				pic_upload[i].setButtonDisabled(false);
				pic_upload[i].setButtonImageURL("http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtn.png");
			}
		}
	}	
}
//  累加1函数
var getNums = function () {
		var count = -1;
		return function () {
			return (count += 1);
		};
	};
var numRan = getNums();
var numLocal = 0;
function load(partId, $uploadBtn, $mDataList){
	partId = (partId == undefined ? 0 : (partId*2+2));
	SWFUpload.WINDOW_MODE = {WINDOW:'opaque',TRANSPARENT : "transparent", OPAQUE : "opaque" };
	var choose_local_pic, arr2 = [];
	var once = true;
	var uploadBtn = $uploadBtn;
	var mDataList = $mDataList;
	if(!($uploadBtn.is($('#allPart .uploadBtn')))){
		$uploadBtn.eq(0).attr("id",'choose_local_pic_'+numLocal);
		mDataList.eq(0).attr("id",'case_space_list_'+numLocal);
		arrTemp.push(numLocal);
	}else{
		numLocal = $mDataList.length;
		for (var i = 0; i < $mDataList.length;i++){
			if(partId == 0){
				numRand = numRan();
				arrTemp.push(numRand);
				arr2 = arrTemp;
				$uploadBtn.eq(i).attr("id",'choose_local_pic_'+arrTemp[i]);
			}else{
				if(i >= ($mDataList.length - 2)){
					if(i == ($mDataList.length - 2)){
						numRand = numRan();
						numRand = numRan();
						arrTemp.push(numRand);
					}else{
						numRand = numRan();
						arrTemp.push(numRand);
					}
					if(i == ($mDataList.length - 2) && once){
						arrTemp.splice(numRand-2,1);
						once = false;
					}
				}
			}
			mDataList.eq(i).attr("id",'case_space_list_'+arrTemp[i]);
		}
	}
	var myDate = new Date();
	var photo_upload_url=CONFIG.photo_upload_url+'?'+myDate.getTime();
	if(partId != 0){
		arr2 = arrTemp.slice($('.mDataList').length - uploadBtn.length-1);
	}
	if(!($uploadBtn.is($('#allPart .uploadBtn')))){
		if(partId != 0){
			$uploadBtn.eq(0).attr("id",'choose_local_pic_'+numLocal);
		}
		pic_upload[numLocal] = new SWFUpload({
			// Backend Settings
			upload_url: photo_upload_url,//"http://xiu.jiaju.sina.com.cn/api/Photo/upload/",
	        post_params: CONFIG.photo_upload_post_params,//{"LUE" : "<?php echo base64_encode($_COOKIE['LUE']);?>", "LUP" : "<?php echo base64_encode($_COOKIE['LUP']);?>"},

			// File Upload Settings
			file_queue_limit:0,
			file_upload_limit:0,
			file_size_limit : "2048",	// 2MB
			file_types : "*.jpg;*.jpeg;*.gif;*.png;",
			file_types_description : "Image Files",
			button_action : SWFUpload.BUTTON_ACTION.SELECT_FILES,
			// Event Handler Settings (all my handlers are in the Handler.js file)
			swfupload_loaded_handler:handler.flashReady,
			file_queued_handler : handler.fileQueued,
			file_queue_error_handler : handler.fileQueueError,
			file_dialog_complete_handler : handler.fileDialogComplete,
			upload_start_handler : handler.uploadStart,
			// upload_progress_handler : handler.uploadProgress,
			upload_error_handler : handler.uploadError,
			upload_success_handler : handler.uploadSuccess,
			upload_complete_handler : handler.uploadComplete,

			// Button Settings
			button_width:90,
			button_height:90,
			button_image_url : "http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtn.png",
			button_placeholder_id : "choose_local_pic_" + numLocal,
			button_cursor:SWFUpload.CURSOR.HAND,
			
			// Flash Settings
			flash_url : "http://static.jiaju.com/jiaju/com/images/s/swfupload.swf",
			custom_settings : {progressTarget : "case_space_list_"+numLocal }, 
			// Debug Settings
			debug: true
		});

	}else{
		for(var mNum=0; mNum<uploadBtn.length; mNum++){
			if(partId != 0){
				$uploadBtn.eq(mNum).attr("id",'choose_local_pic_'+arr2[mNum]);
				partId = partId - 2;
			}
					pic_upload[arr2[mNum]] = new SWFUpload({
					// Backend Settings
					upload_url: photo_upload_url,//"http://xiu.jiaju.sina.com.cn/api/Photo/upload/",
			        post_params: CONFIG.photo_upload_post_params,//{"LUE" : "<?php echo base64_encode($_COOKIE['LUE']);?>", "LUP" : "<?php echo base64_encode($_COOKIE['LUP']);?>"},

					// File Upload Settings
					file_queue_limit:0,
					file_upload_limit:0,
					file_size_limit : "2048",	// 2MB
					file_types : "*.jpg;*.jpeg;*.gif;*.png;",
					file_types_description : "Image Files",
					button_action : SWFUpload.BUTTON_ACTION.SELECT_FILES,
					// Event Handler Settings (all my handlers are in the Handler.js file)
					swfupload_loaded_handler:handler.flashReady,
					file_queued_handler : handler.fileQueued,
					file_queue_error_handler : handler.fileQueueError,
					file_dialog_complete_handler : handler.fileDialogComplete,
					upload_start_handler : handler.uploadStart,
					// upload_progress_handler : handler.uploadProgress,
					upload_error_handler : handler.uploadError,
					upload_success_handler : handler.uploadSuccess,
					upload_complete_handler : handler.uploadComplete,

					// Button Settings
					button_width:90,
					button_height:90,
					button_image_url : "http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtn.png",
					button_placeholder_id : "choose_local_pic_" +arr2[mNum],
					button_cursor:SWFUpload.CURSOR.HAND,
					
					// Flash Settings
					flash_url : "http://static.jiaju.com/jiaju/com/images/s/swfupload.swf",
					custom_settings : {progressTarget : "case_space_list_"+arrTemp[mNum+partId] }, 
					// Debug Settings
					debug: true
				});
		}
	}
}
$(function(){
	//删除图片
	$('body').on("click",'.input_delete_ico',function(){
		var $targetLi = $(this).parent('li'),
			$ul = $targetLi.parent('ul'),
			$li = $ul.children('li'),
			id = $ul.attr('id').split('_').pop(),
			inputIndexof = $li.find("input").attr("name").lastIndexOf("_"),
			inputName = $li.find("input").attr("name").substring(0,inputIndexof);
		if ( $li.length > 4 ){
			pic_upload[id].setButtonDisabled(true);
			pic_upload[id].setButtonCursor(SWFUpload.CURSOR.ARROW);
			pic_upload[id].setButtonImageURL("http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtnGrey.png");
		} else{
			if(pic_upload[id]){
				pic_upload[id].setButtonDisabled(false);
				pic_upload[id].setButtonImageURL("http://static.jiaju.com/jiaju/com/images/lejuzx/uploadBtn.png");
			}
		}
		$targetLi.remove();
		for( var i=0; i<$ul.children('li').length; i++){
			$ul.children('li').eq(i).find("input").attr("name",inputName+"_"+i);
		}
	});
	load(undefined, $('#allPart .uploadBtn'), $('#allPart .mDataList'));
	load(undefined, $('#part_00 .uploadBtn'), $('#part_00 .mDataList'));
});