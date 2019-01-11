$(function() {
	$(".t_files").collapse({show: function(){
		this.animate({
			opacity: 'toggle', 
			height: 'toggle'
			}, 300);
		},
			hide : function() {
		this.animate({
			opacity: 'toggle', 
			height: 'toggle'
			}, 300);
		}
	});

	$("a.gallery").fancybox();

});