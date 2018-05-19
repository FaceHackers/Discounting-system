$(function(){
	//preventDefault for a href="#"
    $(document).on("click", 'a[href="#"]', function(e){ e.preventDefault(); });
})


// Common ajax method
var commonFormFn = function(){
    return {
        loading: [],
        active: false,
        prevUrl: null,
        default: {
            loading_title: '资料处理中',
            loading_content: '请勿关闭或重新整理视窗',
            onSuccess: null,
            onBefore: null,
            onError: null,
            onFail: null,
            onThen: null,
            onComplete: null
        },
        post: function(ajaxurl, postdata, opt, upload){
            var that = this,
                fn = $.extend(true, {}, this.default, opt || {});
            if (upload === true) {
            	var cache = false,
            		contentType = false,
            		processData = false;
            } else {
            	var cache = true,
            		contentType = 'application/x-www-form-urlencoded',
            		processData = true;
            }

            //Prevent duplicate sending
            if(!this.prevent(ajaxurl)) return false;

            this.prevUrl = ajaxurl;

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: postdata || {},
                dataType: 'json',
                cache: cache,
                contentType: contentType,
                processData: processData,
                beforeSend: function(){
                    that.onbefore(ajaxurl, fn.onBefore || null, fn.loading_title, fn.loading_content);
                },
                error: fn.onError || this.onerror,
                success: function(res){
                    if(res.code=='100'){
                        (fn.onSuccess) ? fn.onSuccess(res) : function(){}
                    } else {
                        //(fn.onError) ? fn.onError(res, res.code, res.msg) : ajaxHandler.error(res, res.code, res.msg);
                        (fn.onFail) ? fn.onFail(res, res.code, res.msg) : that.onerror(res, res.code, res.msg);
                    }
                    if(fn.onThen) fn.onThen(res);
                },
                complete: function(){
                    that.oncomplete(ajaxurl, fn.onComplete || null);
                }
            });
        },
        onbefore: function(url, callback, title, content){
            this.loading[url] = popup.loading(title, content);
            if(callback && typeof callback=="function"){
                var res = callback();
                if(res===false) return false;
            }
        },
        onerror: function(xhr, status, err){
            var errMsg = err || xhr;
            popup.alert('系統錯誤', '請重新嘗試或通知管理人員<br>ERROR:'+errMsg+'');
        },
        oncomplete: function(url, callback){
        	var that = this;
            this.active = false;

            if(this.loading[url]){
            	// 防止jQuery confirm無法立即開了之後關閉會發生錯誤
            	try {
            		this.loading[url].close();
            	} catch(e) {
            		setTimeout(function(){
            			that.loading[url].close();
            		}, 50);
            	}
            }

            if(callback && $.isFunction(callback)) callback();
            // $.AdminLTE.layout.fix();
        },
        prevent: function(url){
            if(this.active==true && this.prevUrl==url){
                console.log('Duplicate ajax submit detect, ajax send has been blocked.');
                return false;
            }
            this.active = true;
            return true;
        }
    }
}
var sendFun = new commonFormFn();


var jqGridFn = function(){
    var fn = {
        grids: {},
        currentId : null,
        _init: function(){
            var _this = this;
            $(window).resize(function(){
                _this.resizeAll();
            });
        },
        _getElem: function(selector){
            elem = typeof selector=='string' ? $(selector).eq(0) : selector.eq(0);
            return elem;
        },
        _pushElem: function(elem){
            var _this = this,
                uuid = generateUUID();
            elem.attr('uuid', uuid);
            _this.currentId = uuid;
            _this.grids[uuid] = {item: elem, timer: null};
        },
        _removeElem: function(elem){
            var _this = this;
            var uuid = typeof elem == 'string' ? elem : elem.attr('uuid');
            delete _this.grids[uuid];
        },
        _removeAll: function(){
            $.each(_this.grids, function(k, v){
                delete _this.grids[k]; 
            });
            _this.grids = {};
        },
        check: function(selector){
            var _this = this,
                elem = _this._getElem(selector),
                uuid = elem.attr('uuid') || '';
            
            if(uuid){
                return (_this.grids[uuid]) ? true : false;
            }
            return false;
        },
        create: function(selector, colN, colM, callback, opt){
            var _this = this,
                elem = _this._getElem(selector),
                defaults = {
                    datatype: 'local',
					rowNum: 10000,
					cmTemplate: {
						sortable: true
					},
					width: $('.box-body').width() - 20,
					height: $(window).height() - $('.box-body').offset().top - 90,
					hoverrows: true,
					autoencode: true,
					ignoreCase: true,
					viewrecords: true,
					footerrow: true,
					sortable: true,
					sortname: colM[0].name,
					sortorder: 'asc',
					colNames: colN,
					colModel: colM,
                    gridComplete: function(){
                        //部分瀏覽器(Chrome)會有 1px 的誤差
                        var jqbdiv = $(this).closest('.ui-jqgrid-bdiv');
                        $(this).parent().css({overflowX:($(this).width()-$(this).parent().width()==1)?'hidden':'auto'});
                        //也會有另一個 1px 誤差的狀況
                        jqbdiv.children('div:eq(0)').css({overflowX: ($(this).width()-jqbdiv.width()<=2)? 'hidden':'auto'});
                    },
                    loadComplete: function(res){
                        if (callback && $.isFunction(callback)) {
                        	callback();
                        } else {
                        	var to_sum = {};
						  	to_sum[colM[0].name] = '總計:(共 0 筆)';
						  	var cnt = 0;
						  	if(res){
						    	var row = res['rows'];
						  		for(var i = 0;i < row.length;i++){
						  			cnt++;
						  		}

							  	to_sum[colM[0].name] = '總計:(共 ' + cnt + ' 筆)';
						  	}
						  	elem.jqGrid('footerData', 'set', to_sum);
                        }
                    }
                },
                options = $.extend({}, defaults , opt || {}); 

            _this._pushElem(elem);
            elem.jqGrid(options);
            elem.jqGrid('filterToolbar',{stringResult: true, searchOnEnter: false});
            return elem;
        },
        update: function(selector, data){
            var _this = this,
                elem = _this._getElem(selector);
            // elem.setGridParam(data).trigger('reloadGrid');
            elem.jqGrid('setGridParam', { datatype: 'local', data: data}).trigger('reloadGrid');
        },
        refresh: function(selector){
            this.reload(selector);
        },
        clear: function(selector){
        	var _this = this,
                elem = _this._getElem(selector);
            elem.jqGrid('clearGridData');
        },
        clearCurrent: function(){
        	var _this = this,
                elem = _this.grids[_this.currentId].item;
            elem.jqGrid('clearGridData');
        },
        reload: function(selector){
            var _this = this,
                elem = _this._getElem(selector);
            elem.trigger('reloadGrid');
        },
        reloadCurrent: function(){
            var _this = this,
                elem = _this.grids[_this.currentId].item;
            elem.trigger('reloadGrid');
        },
        destroy: function(selector){
            var _this = this,
                elem = _this._getElem(selector);
            _this._removeElem(elem);
            elem.jqgrid('destroy');
        },
        destroyAll: function(){
            var _this = this;
            $.each(_this.grids, function(index, elem){
                var item = elem.item || null;
                if(item && item.length){
                    item.jqgrid('destroy');
                }
            });
            _this.removeAll();
        },
        resize: function(selector){
            var _this = this,
                elem = _this._getElem(selector);
            elem.setGridWidth($(window).width());
        },
        resizeAll: function(){
            var _this = this;
            $.each(_this.grids, function(index, obj){
                obj.item = obj.item || null;
                obj.timer = obj.timer || null;
                if(obj.item && obj.item.length && obj.item.is(':visible')){
                    if(obj.timer) clearTimeout(obj.timer);
                    obj.timer = setTimeout(function(){
                        obj.item.setGridWidth(obj.item.closest('.box-body').width());
                    }, 300);
                }
            });
        },
        getData: function(selector){
        	var _this = this,
            	elem = _this._getElem(selector);
            	data = elem.jqGrid('getGridParam', 'data');
            return data;
        }
    };
    fn._init();
    return fn;
};
var jgrid = new jqGridFn();


function open_win(url, title, scale, ratio, onopen, t_type){
	if (ratio == null) {
		ratio = [1024, 768];
	}
	if (scale == null) {
		scale = 0.9;
	}
	if (t_type == null) {
		t_type = 'top';
	}
	$.fancybox({
		href: url,
		type: 'iframe',
		maxWidth: $(window).innerWidth()*scale,
		maxHeight: $(window).innerHeight()*scale,
		fitToView: false,
		width: $(window).innerWidth()*scale,
		height: $(window).innerHeight()*scale,
		autoSize: false,
		closeClick: false,
		openEffect: 'none',
		closeEffect: 'none',
		scrolling: 'no',
		padding : 6,
		iframe: {
			preload: false
		},
		helpers: {
			title: {
				type: t_type
			}
		},
		beforeLoad: function() {
			this.title = title
		},
		afterLoad: function(current, previous) {
			if (onopen != null) {
				onopen(current, previous);
			}
		}
	});
}

function open_win_wh(url, title, w, h, onopen){
	$.fancybox({
		href: url,
		type: 'iframe',
		maxWidth: w,
		maxHeight: h,
		fitToView: false,
		width: w,
		height:h,
		autoSize: false,
		closeBtn: false,
		closeClick: false,
		openEffect: 'none',
		closeEffect: 'none',
		scrolling: 'no',
		padding : 6,
		iframe: {preload: false},
		helpers: {
			overlay: {
				showEarly : true
			},
			title: {
				type: 'top'
			}
		},
		beforeLoad: function() {
			this.title = title;
		},
		afterLoad: function(current, previous) {
			if (onopen!=null) {
				onopen(current, previous);
			}
		}
	});
}

function generateUUID() {
    var d = new Date().getTime(),
        r1 = Math.floor(Math.random()*9+1).toString(),
        r2 = (Math.random()).toString(16),
        uuid = (  r2 + '' + (( r1 + '' + d) * 8).toString(16) ).substr(-15);
    return uuid;
}

function number_format(number, deciNum, ksplit){
    if(deciNum==undefined || isNaN(deciNum)) deciNum = 0;
    var ksplit = (ksplit===true? ',': ksplit) || '';
    number = (number+'').replace(',');
    number = (number*1).toFixed(deciNum);
    if(ksplit){
        x = number.split('.');
        x1 = x[0];
        x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ksplit + '$2');
        }
        number = x1 + x2;
    }
    return number;
}

function urldecode(str) {
	return decodeURIComponent((str+'').replace(/\+/g, '%20'));
}