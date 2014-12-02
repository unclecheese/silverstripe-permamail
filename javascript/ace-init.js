(function($) {	
$('textarea.ace').entwine({

	Interval: null,

	onmatch: function () {		
		this.setInterval(window.setInterval(function () {
			console.log('ping');
			if(this.is(':visible')) {
				this.ace({
					theme: 'chrome',
					lang: 'html'
				});

				window.clearInterval(this.getInterval());
			}		
		}.bind(this), 100));
	},

	onunmatch: function () {
		window.clearInterval(this.getInterval());
	}

});
})(jQuery);