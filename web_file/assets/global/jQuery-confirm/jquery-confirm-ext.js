/* 2017-04-26 彥彬改
 * Custom extension method with jquery-confirm.
 * jquery-confirm.js must loaded before this.
 *
 * Example
 * ------------------------------------------------------
 * Change theme:
 * 		popup.theme.alert = 'light';
 *
 * Use return object:
 * 		var loader = popup.loading('hello', 'world');
 * 	 	loader.close();
 *
 * ------------------------------------------------------
 */
var jqConfirmExt = function(){
	var _this = this;
	var colClass = 'col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 col-xs-12 col-xs-offset-0';
	return {
		theme :{
			// 'light','dark','supervan','material','bootstrap'
			alert:'material',
			confirm:'material',
			dialog:'material',
			block:'supervan',
			loading:'supervan'
		},
		alert: function(title, content, callback, options){
			var opt = $.extend({},{
				title: title || false,
				content: content || false,
				theme: popup.theme.alert,
				buttons: {
					'關閉': {
		    			btnClass: 'btn-default',
		    			action: callback || function(){}
		    		}
				},
				columnClass: colClass,
				onOpen: function(){
					$('button').focus();
				}
			},  options || {});

			return $.alert(opt);
		},
		confirm: function(title, content, onConf, onCancel, options){
			var opt = $.extend({},{
				title: title || false,
				content: content || false,
				theme: popup.theme.confirm,
				icon:'glyphicon glyphicon-question-sign',
				backgroundDismiss: false,
				buttons: {
					'確定': {
		    			btnClass: 'btn-default',
		    			action: onConf || function(){}
		    		},
		    		'取消': {
		    			action: onCancel || function(){}
		    		}
				},
				columnClass: colClass,
				onOpen: function(){
					$('button').focus();
				}
			},  options || {});
			
			return $.confirm(opt);
		},
		dialog: function(title, content, open, close, options){
			var opt = $.extend({},{
				title: title || false,
				content: content || false,
				theme: popup.theme.dialog,
				backgroundDismiss: true,
				closeIcon: true,
				closeIconClass: 'fa fa-close',
				columnClass: colClass,
				onOpen: open || function(){},
				onClose: close || function(){}
			},  options || {});
			
			return $.dialog(opt);
		},
		block: function(title, content, open, close, options){
			var opt = $.extend({},{
				title: title,
				content: content,
				theme: popup.theme.block,
				backgroundDismiss: false,
				closeIcon: false,
				columnClass: colClass,
				onOpen: open || function(){},
				cancel: close || function(){}
			},  options || {});
			return $.dialog(opt);
		},
		loading: function(title, content, open, close, options){
			var opt = $.extend({},{
				title: title || 'Loading',
				content: content || false,
				theme: popup.theme.loading,
				icon:'fa fa-spinner fa-spin',
				backgroundDismiss: false,
				closeIcon: false,
				columnClass: colClass,
				onOpen: open || function(){},
				cancel: close || function(){}
			},  options || {});
			
			return $.dialog(opt);
		},
		custom: function(title, content, onConf, options){
			var opt = $.extend({},{
				title: title,
				content: content,
				theme: popup.theme.confirm,
				//icon:'glyphicon glyphicon-question-sign',
				backgroundDismiss: true,
				confirmButton: '確定',
				cancelButton: '取消',
				columnClass: colClass,
				confirm:onConf || function(){},
				cancel: function(){}
			},  options || {});

			return $.dialog(opt);
		}
	}
};
var popup = new jqConfirmExt();