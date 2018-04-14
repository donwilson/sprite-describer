	
	;jQuery(document).ready(function($) {
		$("#content .message").on('click', function(e) {
			if($(this).hasClass("message_no_collapse")) {
				return;
			}
			
			e.preventDefault();
			
			$(this).slideUp(250, function() {
				$(this).empty().remove();
			});
		});
		
		$(".spritesheet_description input[type='text']").on('focus', function() {
			$(this).select();
		});
		
		$(".small_number").on('blur change', function() {
			var refined_value = $(this).val();
			
			refined_value = refined_value.replace(/[^\-0-9\.]+/, "");
			refined_value = refined_value.replace(/^0+/, "");
			
			if("" === refined_value) {
				refined_value = "0";
			}
			
			$(this).val( refined_value );
		});
		
		$(".sprite_editor .show_view").on('click', function(e) {
			var sprite_editor, which_view, focus_on;
			
			e.preventDefault();
			
			sprite_editor = $(this).parents(".sprite_editor");
			which_view = $(this).attr('data-view') || false;
			
			if(!which_view || !sprite_editor.find(".view_"+ which_view).length) {
				console.log(".show_view :: which_view="+ which_view);
				
				return;
			}
			
			focus_on = $(this).attr('data-view-focus') || false;
			
			sprite_editor.find(".viewable").addClass("view__hidden");
			sprite_editor.find(".view_"+ which_view).removeClass("view__hidden");
			
			if(sprite_editor.find(focus_on).length) {
				sprite_editor.find(focus_on).focus().select();
			}
		});
		
		$(".sprite_editor .ignore_sprite").on('change', function() {
			var checked = $(this).prop('checked') || false;
			
			$(this).parents(".sprite_editor .view_edit").find(".sprite_key").prop('disabled', checked);
		});
		
		$(".sprite_editor .sprite_edit_form").on('submit', function(e) {
			var parent_editor = $(this).parents(".sprite_editor");
			
			e.preventDefault();
			
			$.ajax({
				'method':	"POST",
				'url':		"index.php",
				'data':		$(this).serialize(),
				'cache':	false,
				'success':	function(data) {
					if(!data || !data.status) {
						console.log("malformed data:");
						console.log(data);
						
						return;
					}
					
					if("error" == data.status) {
						if(data.cargo && data.cargo.message) {
							alert("Error: "+ data.cargo.message);
						}
						
						return;
					}
					
					parent_editor.find(".viewable").addClass("view__hidden");
					parent_editor.find(".view_standard").removeClass("view__hidden");
					
					if("undefined" != typeof data.cargo.sprite_key) {
						if("" !== data.cargo.sprite_key) {
							parent_editor.find(".view_standard .sprite_key").text(data.cargo.sprite_key);
						} else {
							parent_editor.find(".view_standard .sprite_key").html("&nbsp;");
						}
					}
				},
				'error':	function() {
					alert("Error with .sprite_edit_form");
				}
			});
		});
	});
	