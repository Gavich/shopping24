var Varnish = {
	callBacks : {},
	templates : {},
	layoutMessages : {},
	block_data : {},
	params : {},
	topCart : {
		wrapper : null,
		reload_id : '',
		loaded: false
	},

	Init : function(params) {
		this.params = params;
		this.onComplete = this.updateBlocks.bind(this);
	},
	updateAll : function() {
		//add classnames to needest elements
		jQuery('*[varnishclassadd]').addClass('varnish-reload');

		var data = {blocks: {}};
		var count = 0;

		jQuery('.varnish-reload').each(function() {
			data.blocks[jQuery(this).attr('id')] = {};
			data.blocks[jQuery(this).attr('id')]['name'] = jQuery(this).attr('rel');
			data.blocks[jQuery(this).attr('id')]['mode'] = jQuery(this).attr('mode');
			count++;
		});
		if (!count) return;

		data.messages = this.layoutMessages;

		data.referer_uenc = this.params.REFERER.URL;

		this.block_data = data;

		var url = this.params.UPDATE_URL;
		this.ajaxBlocksUpdate(url, data);
	},

	ajaxBlocksUpdate : function(url, data_in) {
		jQuery.post(
			url,
			data_in,
			this.onComplete,
			'json'
		)

	},

	updateBlocks : function(data) {
		for(var id in data.blocks) {
			if (data.blocks[id] != 'error') {
				if (this.templates[id] && typeof this.templates[id] != 'undefined') {
					var html = this.templates[id].evaluate(data.blocks[id]);
				} else {
					var html = data.blocks[id];
				}
				if (typeof html == 'string') {
					switch(this.block_data.blocks[id]['mode'])
					{
						case 'append':
							jQuery('#' + id).append(html);
							break;
						case 'replace':
							jQuery('#' + id).replaceWith(html)
							break;
						default:
							jQuery('#' + id).html(html);
					}
					if (typeof Varnish.callBacks[id] == 'function') {
						setTimeout(Varnish.callBacks[id], 0);
					}
				}
			}
		}
	},

	addCallBack : function(id, callback){
		if (typeof callback != 'function') {
			callback = function(){};
		}
		this.callBacks[id] = callback;
	},
	addMessagesInit : function(message_type) {
		this.layoutMessages[message_type] = message_type;
	},
	addTemplate : function (id, templateText) {
		this.templates[id] = new Template(templateText, this.params.templateSyntax);
	},
	setTopCart : function(options){
		if (typeof options.wrapperHTML == 'string') {
			this.topCart.wrapper = jQuery(options.wrapperHTML);
		}
		this.topCart.reload_id = options.reload_id;
		this.topCart.loaded = false;
		this.topCart.parent_id = options.parent_id;
		this.topCart.action = options.mode;
	},
	getTopCart : function() {
		if (!this.topCart.loaded) {
			var _self = this;
			jQuery.post(this.params.TOPCARTCLICK_URL, function(data) {
				jQuery("#" + _self.topCart.reload_id).replaceWith(data);
				jQuery('#block-cart').addClass('display-cart');
				jQuery('#topcartlink').addClass(' shop');
				Varnish.topCart.loaded = true;
				showshopping();
				display();
			});
			return true;
		} else {
			return false;
		}
	}
};