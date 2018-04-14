<?php
	require_once(__DIR__ ."/config.php");
	
	//die("<pre>". print_r(array('_SERVER' => $_SERVER), true) ."</pre>");
	
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	
	function die_ajax($status="success", $cargo=array()) {
		header("Content-Type: application/json");
		
		die(json_encode(array(
			'status'	=> $status,
			'cargo'		=> $cargo,
		)));
	}
	
	function calculate_spritesheet_info($spritesheet) {
		if(empty($spritesheet['id']) || empty($spritesheet['filename']) || !file_exists(DIR_SPRITESHEET . $spritesheet['filename'])) {
			return false;
		}
		
		$info = array();
		
		// file extension
		$file_exts = explode(".", $spritesheet['filename']);
		
		if(count($file_exts) > 1) {
			$info['ext'] = strtolower(trim(array_pop($file_exts)));
		}
		
		if(!isset($info['ext']) || !in_array($info['ext'], array("jpg", "jpeg", "gif", "png"))) {
			// unacceptable spritesheet file
			return false;
		}
		
		// width + height	
		list($info['width'], $info['height']) = @getimagesize(DIR_SPRITESHEET . $spritesheet['filename']);
		
		// calculate spritesheet sprite counts
		// sprite_num: ceil( (image_dimension - offset) / (sprite_dimension + padding) )
		if(!empty($info['width']) && !empty($spritesheet['sprite_width'])) {
			$info['num_sprites_x'] = ceil( (($info['width'] - $spritesheet['offset_x']) / ($spritesheet['sprite_width'] + $spritesheet['padding_x'])) );
		}
		
		if(!empty($info['height']) && !empty($spritesheet['sprite_height'])) {
			$info['num_sprites_y'] = ceil( (($info['height'] - $spritesheet['offset_y']) / ($spritesheet['sprite_height'] + $spritesheet['padding_y'])) );
		}
		
		return $info;
	}
	
	function pull_sprites($spritesheet) {
		if(empty($spritesheet['id'])) {
			return array();
		}
		
		$raw_sprites = get_results("
			SELECT *
			FROM `spritesheet_sprite`
			WHERE
				`spritesheet` = '". esc_sql($spritesheet['id']) ."'
			ORDER BY `offset_y` ASC, `offset_x` ASC
		");
		
		if(empty($raw_sprites)) {
			return array();
		}
		
		return $raw_sprites;
	}
	
	$messages = array();
	
	$user_action = (!empty($_REQUEST['action'])?strtolower(trim($_REQUEST['action'])):"");
	
	$spritesheet_id = false;
	
	switch($user_action) {
		case 'upload':
			if(isset($_FILES['spritesheet']) && (UPLOAD_ERR_OK === $_FILES['spritesheet']['error'])) {
				$new_filename = trim($_FILES['spritesheet']['name'], " /\\");
				$file_exts = explode(".", $new_filename);
				$file_ext = array_pop($file_exts);
				
				if(("" !== $new_filename) && preg_match("#\.(jpe?g|png|gif)$#si", $new_filename)) {
					while(file_exists(DIR_SPRITESHEET . $new_filename)) {
						$new_filename = implode(".", $file_exts) ."_". substr(md5(time() . microtime(true) ."_". rand(1, 9999)), 0, 3) .".". $file_ext;
					}
					
					if(@move_uploaded_file($_FILES['spritesheet']['tmp_name'], DIR_SPRITESHEET . $new_filename)) {
						@mysql_query("
							INSERT INTO `spritesheet`
							SET
								`filename`	= '". esc_sql($new_filename) ."'
						");
						
						$insert_id = @mysql_insert_id();
						
						if(!empty($insert_id)) {
							$spritesheet_id = $insert_id;
							
							$messages[] = array(
								'type'		=> "success",
								'message'	=> "Saved new spritesheet #". $spritesheet_id ." for '". $new_filename ."'!",
							);
						} else {
							@unlink(DIR_SPRITESHEET . $new_filename);
							
							$messages[] = array(
								'type'		=> "error",
								'message'	=> "Failed to insert spritesheet (possible database error: ". mysql_error() .")",
							);
						}
					} else {
						$messages[] = array(
							'type'		=> "error",
							'message'	=> "Spritesheet file could not be saved",
						);
					}
				} else {
					$messages[] = array(
						'type'		=> "error",
						'message'	=> "File uploaded is not an image and cannot be used for a spritesheet",
					);
				}
			} else {
				$messages[] = array(
					'type'	=> "error",
					'message'	=> "Spritesheet file not uploaded",
				);
			}
		break;
		
		case 'describe_spritesheet':
			$spritesheet_id = (!empty($_POST['spritesheet'])?get_var("SELECT `id` FROM `spritesheet` WHERE `id` = '". esc_sql($_POST['spritesheet']) ."'"):false);
			
			if(!empty($spritesheet_id) && !is_null($spritesheet_id)) {
				$update_cols = array(
					"sprite_width",
					"sprite_height",
					"offset_x",
					"offset_y",
					"padding_x",
					"padding_y",
				);
				
				$updates = array();
				
				foreach($update_cols as $update_col) {
					if(!isset($_POST[ $update_col ])) {
						continue;
					}
					
					if(!is_numeric($_POST[ $update_col ])) {
						$messages[] = array(
							'type'		=> "error",
							'message'	=> "Value for '". $update_col ."' was not numeric and thus not saved",
						);
						
						continue;
					}
					
					$update_value = (int)$_POST[ $update_col ];
					
					$updates[] = "`". esc_sql($update_col) ."` = '". esc_sql( $update_value ) ."'";
				}
				
				if(!empty($updates)) {
					@mysql_query("
						UPDATE `spritesheet`
						SET
							". implode(", \n", $updates) ."
						WHERE
							`id` = '". esc_sql($spritesheet_id) ."'
					");
					
					$messages[] = array(
						'type'		=> "success",
						'message'	=> "Successfully saved settings for spritesheet",
					);
				}
			} else {
				$messages[] = array(
					'type'		=> "error",
					'message'	=> "Spritesheet not found",
				);
			}
		break;
		
		case 'set_sprite':
			$spritesheet_id = (!empty($_POST['spritesheet'])?get_var("SELECT `id` FROM `spritesheet` WHERE `id` = '". esc_sql($_POST['spritesheet']) ."'"):false);
			
			if(empty($spritesheet_id) || is_null($spritesheet_id)) {
				die_ajax('error', array(
					'action'	=> $user_action,
					'message'	=> "Spritesheet not found",
				));
			}
			
			if(!isset($_POST['offset_x']) || ("" === ($offset_x = trim(strtolower($_POST['offset_x'])))) || !is_numeric($offset_x) || !isset($_POST['offset_y']) || ("" === ($offset_y = trim(strtolower($_POST['offset_y'])))) || !is_numeric($offset_y)) {
				die_ajax('error', array(
					'action'		=> $user_action,
					'spritesheet'	=> $spritesheet_id,
					'message'		=> "Sprite details not provided",
				));
			}
			
			$sprite_key = (isset($_POST['sprite_key'])?trim(strtolower($_POST['sprite_key'])):"");
			
			// check if sprite key is already used and not at this location
			$existing_sprite_key = get_row("SELECT * FROM `spritesheet_sprite` WHERE `spritesheet` = '". esc_sql($spritesheet_id) ."' AND `key` = '". esc_sql($sprite_key) ."'");
			
			if(!empty($existing_sprite_key['id']) && (($existing_sprite_key['offset_x'] != $offset_x) || ($existing_sprite_key['offset_y'] != $offset_y))) {
				// sprite key already used at another location
				die_ajax('error', array(
					'action'		=> $user_action,
					'spritesheet'	=> $spritesheet_id,
					'message'		=> "Key '". $sprite_key ."' already used at another location (x: ". $existing_sprite_key['offset_x'] .", y: ". $existing_sprite_key['offset_y'] .")",
				));
			}
			
			$ignore = (!empty($_POST['ignore'])?"1":"0");
			
			@mysql_query("
				INSERT INTO `spritesheet_sprite`
				SET
					`spritesheet`	= '". esc_sql($spritesheet_id) ."',
					`key`			= '". esc_sql($sprite_key) ."',
					`ignore`		= '". esc_sql($ignore) ."',
					`offset_x`		= '". esc_sql($offset_x) ."',
					`offset_y`		= '". esc_sql($offset_y) ."'
				ON DUPLICATE KEY UPDATE
					`key`		= VALUES(`key`),
					`ignore`	= VALUES(`ignore`),
					`offset_x`	= VALUES(`offset_x`),
					`offset_y`	= VALUES(`offset_y`),
					`id`		= LAST_INSERT_ID(`id`)
			");
			
			$insert_id = @mysql_insert_id();
			
			if(empty($insert_id)) {
				die_ajax('error', array(
					'action'		=> $user_action,
					'spritesheet'	=> $spritesheet_id,
					'message'		=> "Failed to insert sprite (possible database error: ". mysql_error() .")",
				));
			}
			
			die_ajax('success', array(
				'action'		=> $user_action,
				'spritesheet'	=> $spritesheet_id,
				'sprite'		=> $insert_id,
				'offset_x'		=> $offset_x,
				'offset_y'		=> $offset_y,
				'sprite_key'	=> $sprite_key,
				'offset_x'		=> !empty($ignore),
			));
		break;
		
		case 'export_spritesheet':
			$spritesheet = (!empty($_GET['spritesheet'])?get_row("SELECT * FROM `spritesheet` WHERE `id` = '". esc_sql($_GET['spritesheet']) ."'"):false);
			
			if(empty($spritesheet['id']) || is_null($spritesheet)) {
				die("Spritesheet not found");
			}
			
			if((false === ($spritesheet_info = calculate_spritesheet_info($spritesheet))) || empty($spritesheet_info['num_sprites_x']) || empty($spritesheet_info['num_sprites_y'])) {
				die("Spritesheet corrupt and could not be exported");
			}
			
			$file_bits = explode("/", str_replace("\\", "/", $spritesheet['filename']));
			$actual_filename = array_pop($file_bits);
			$file_exts = explode(".", $actual_filename);
			$file_ext = array_pop($file_exts);
			$file_prefix = implode(".", $file_exts);
			
			$sprites = array(
				'raw'		=> array(),
				'by_key'	=> array(),
			);
			$raw_sprites = pull_sprites($spritesheet);
			
			foreach($raw_sprites as $raw_sprite) {
				if(!empty($raw_sprite['ignore'])) {
					continue;
				}
				
				$sprite = array(
					'offset'	=> array(
						'x'	=> $raw_sprite['offset_x'],
						'y'	=> $raw_sprite['offset_y'],
					),
				);
				
				if("" !== $raw_sprite['key']) {
					$sprite['key'] = $raw_sprite['key'];
					
					$sprites['by_key'][ $raw_sprite['key'] ] = array(
						'x'	=> $sprite['offset_x'],
						'y'	=> $sprite['offset_y'],
					);
				}
				
				$sprites['raw'][] = $sprite;
			}
			
			header("Content-Disposition: attachment; filename=". $file_prefix .".json");
			header("Content-Type: application/json");
			
			$export_data = array(
				'export_info'	=> array(
					'date'		=> (int)time(),
				),
				'file'	=> array(
					'filename'	=> $actual_filename,
					'width'		=> (int)$spritesheet_info['width'],
					'height'	=> (int)$spritesheet_info['height'],
				),
				'info'		=> array(
					'sprite'	=> array(
						'width'		=> (int)$spritesheet['sprite_width'],
						'height'	=> (int)$spritesheet['sprite_height'],
						'num_x'		=> (int)$spritesheet_info['num_sprites_x'],
						'num_y'		=> (int)$spritesheet_info['num_sprites_y'],
					),
					'offset'	=> array(
						'x'		=> (int)(isset($spritesheet['offset_x'])?$spritesheet['offset_x']:0),
						'y'		=> (int)(isset($spritesheet['offset_y'])?$spritesheet['offset_y']:0),
					),
					'padding'	=> array(
						'x'		=> (int)(isset($spritesheet['padding_x'])?$spritesheet['padding_x']:0),
						'y'		=> (int)(isset($spritesheet['padding_y'])?$spritesheet['padding_y']:0),
					),
				),
				'sprites'	=> $sprites,
			);
			
			die(json_encode($export_data));
		break;
	}
	
	$spritesheets = get_results("SELECT * FROM `spritesheet` ORDER BY `filename`", 'id');
	$raw_selected_spritesheet = false;
	$selected_spritesheet = false;
	$selected_spritesheet_info = array(
		'width'			=> 0,
		'height'		=> 0,
		'ext'			=> "",
		'num_sprites_x'	=> 0,
		'num_sprites_y'	=> 0,
	);
	$spritesheet_sprites = array();
	
	$min_zoom_level = 1;
	$max_zoom_level = 10;
	$zoom_step = 1;
	$zoom_level = ((!empty($_REQUEST['zoom']) && is_numeric($_REQUEST['zoom']) && ($_REQUEST['zoom'] >= $min_zoom_level) && ($_REQUEST['zoom'] <= $max_zoom_level))?(int)$_REQUEST['zoom']:$min_zoom_level);
	
	if(empty($raw_selected_spritesheet['id']) && !empty($spritesheet_id) && isset($spritesheets[ $spritesheet_id ])) {
		$raw_selected_spritesheet = $spritesheets[ $spritesheet_id ];
	}
	
	if(empty($raw_selected_spritesheet['id']) && !empty($_REQUEST['spritesheet']) && isset($spritesheets[ $_REQUEST['spritesheet'] ])) {
		$raw_selected_spritesheet = $spritesheets[ $_REQUEST['spritesheet'] ];
	}
	
	if(!empty($raw_selected_spritesheet['id'])) {
		if(false !== ($raw_spritesheet_info = calculate_spritesheet_info($raw_selected_spritesheet))) {
			$selected_spritesheet = $raw_selected_spritesheet;
			
			$selected_spritesheet_info = array_merge($selected_spritesheet_info, $raw_spritesheet_info);
			
			$spritesheet_sprites = array();   // sprites[y][x] = sprite
			$raw_sprites = pull_sprites($selected_spritesheet);
			
			if(!empty($raw_sprites)) {
				foreach($raw_sprites as $raw_sprite) {
					if(!isset($spritesheet_sprites[ $raw_sprite['offset_y'] ])) {
						$spritesheet_sprites[ $raw_sprite['offset_y'] ] = array();
					}
					
					$spritesheet_sprites[ $raw_sprite['offset_y'] ][ $raw_sprite['offset_x'] ] = array(
						'key'	=> trim($raw_sprite['key']),
					);
				}
			}
		} else {
			$messages[] = array(
				'type'		=> "error",
				'message'	=> "Spritespeet file not found or corrupt at '". DIR_SPRITESHEET . $raw_selected_spritesheet['filename'] ."'",
			);
			
			$raw_selected_spritesheet = false;
		}
	}
?>
<!doctype html>
<html>
<head>
	<title>Spritesheet Describer</title>
	<link href='https://fonts.googleapis.com/css?family=Lato:400,700,700italic,400italic,900' rel='stylesheet' type='text/css'>
	<style type="text/css">
		<?=file_get_contents(DIR_BASE . "style.css");?>
	</style>
</head>
<body>
	
	<div id="app">
		<aside id="menu">
			<?php if(!empty($selected_spritesheet['id'])): ?>
			<h3 class="header">Current Spritesheet</h3>
			
			<div class="spritesheet_preview">
				<div class="filename" title="<?=$selected_spritesheet['filename'];?>"><?=$selected_spritesheet['filename'];?></div>
				
				<?php if(!empty($selected_spritesheet_info['num_sprites_x']) && !empty($selected_spritesheet_info['num_sprites_y'])): ?>
				<div class="fact">Sprites: <?=number_format($selected_spritesheet_info['num_sprites_x']);?>x<?=number_format($selected_spritesheet_info['num_sprites_y']);?></div>
				<?php endif; ?>
				
				<a href="<?=URL_SPRITESHEET . $selected_spritesheet['filename'];?>" target="_blank" title="Filename: <?=$selected_spritesheet['filename'];?>" class="preview_image_anchor"><img src="<?=URL_SPRITESHEET . $selected_spritesheet['filename'];?>" class="preview_image" alt="<?=$selected_spritesheet['filename'];?>" /></a>
				
				<div class="fact"><a href="?action=export_spritesheet&amp;spritesheet=<?=$selected_spritesheet['id'];?>" title="">Export Spritesheet Data</a></div>
			</div>
			
			<hr />
			<?php endif; ?>
			
			<?php if(!empty($spritesheets)): ?>
			<h3 class="header">Select Spritesheet</h3>
			<form method="get">
				<select name="spritesheet">
					<option value="">Select Sheet:</option>
					<option value="">=============</option>
					<?php foreach($spritesheets as $spritesheet): ?>
					<option value="<?=$spritesheet['id'];?>"<?php if(!empty($selected_spritesheet['id']) && ($selected_spritesheet['id'] == $spritesheet['id'])): ?> selected="selected"<?php endif; ?>><?=$spritesheet['filename'];?></option>
					<?php endforeach; ?>
				</select> <input type="submit" value="Select" />
			</form>
			
			<hr />
			<?php endif; ?>
			
			<h3 class="header">Upload Spritesheet</h3>
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="upload" />
				<input type="file" name="spritesheet" /><br />
				<input type="submit" value="Upload" />
			</form>
		</aside>
		<main id="content">
			<?php if(!empty($messages)): foreach($messages as $message): ?>
				<div class="message message__<?=$message['type'];?>"><?=$message['message'];?></div>
			<?php endforeach; endif; ?>
			
			<?php if(!empty($selected_spritesheet['id'])): ?>
				<h2 class="header">Spritesheet Settings</h2>
				<form method="post" class="spritesheet_description">
					<input type="hidden" name="action" value="describe_spritesheet" />
					<input type="hidden" name="spritesheet" value="<?=$spritesheet['id'];?>" />
					<ul class="line_items">
						<li><label for="describe_spritesheet__sprite_width" title="Width in pixels of sprite">Sprite Width: <input type="text" name="sprite_width" value="<?=(isset($selected_spritesheet['sprite_width'])?htmlentities($selected_spritesheet['sprite_width']):"");?>" id="describe_spritesheet__sprite_width" class="small_number" /></label></li>
						<li><label for="describe_spritesheet__sprite_height" title="Height in pixels of sprite">Sprite Height: <input type="text" name="sprite_height" value="<?=(isset($selected_spritesheet['sprite_height'])?htmlentities($selected_spritesheet['sprite_height']):"");?>" id="describe_spritesheet__sprite_height" class="small_number" /></label></li>
						<li><label for="describe_spritesheet__offset_x" title="Offset in pixels from left of first sprite column">Offset X: <input type="text" name="offset_x" value="<?=(isset($selected_spritesheet['offset_x'])?htmlentities($selected_spritesheet['offset_x']):"");?>" id="describe_spritesheet__offset_x" class="small_number" /></label></li>
						<li><label for="describe_spritesheet__offset_y" title="Offset in pixels from top of first sprite row">Offset Y: <input type="text" name="offset_y" value="<?=(isset($selected_spritesheet['offset_y'])?htmlentities($selected_spritesheet['offset_y']):"");?>" id="describe_spritesheet__offset_y" class="small_number" /></label></li>
						<li><label for="describe_spritesheet__padding_x" title="Space in pixels on x-axis between two sprites">Padding X: <input type="text" name="padding_x" value="<?=(isset($selected_spritesheet['padding_x'])?htmlentities($selected_spritesheet['padding_x']):"");?>" id="describe_spritesheet__padding_x" class="small_number" /></label></li>
						<li><label for="describe_spritesheet__padding_y" title="Space in pixels on y-axis between two sprites">Padding Y: <input type="text" name="padding_y" value="<?=(isset($selected_spritesheet['padding_y'])?htmlentities($selected_spritesheet['padding_y']):"");?>" id="describe_spritesheet__padding_y" class="small_number" /></label></li>
						<li><input type="submit" value="Save" /></li>
						<li><input type="reset" value="Reset" /></li>
					</ul>
				</form>
				
				<h2 class="header">Sprites</h2>
				<form method="get" class="sprite_view_settings">
					<input type="hidden" name="spritesheet" value="<?=$spritesheet['id'];?>" />
					Zoom: <select name="zoom">
						<?php for($i = $min_zoom_level; $i <= $max_zoom_level; $i += $zoom_step): ?>
						<option value="<?=$i;?>"<?php if($i == $zoom_level): ?> selected="selected"<?php endif; ?>><?=number_format($i);?>x</option>
						<?php endfor; ?>
					</select> <input type="submit" value="Go" />
				</form>
				
				<?php if(!empty($selected_spritesheet_info['num_sprites_x']) && !empty($selected_spritesheet_info['num_sprites_y'])): ?>
				<?php
					// Math for CSS
					$preview_width = ($selected_spritesheet['sprite_width'] * $zoom_level);
					$preview_height = ($selected_spritesheet['sprite_height'] * $zoom_level);
					
					$stretched_image_width = ($selected_spritesheet_info['width'] * $zoom_level);
					$stretched_image_height = ($selected_spritesheet_info['height'] * $zoom_level);
				?>
				<table class="spritesheet_sprites" cellspacing="10" cellpadding="0" border="1" bordercolor="#c0c0c0">
					<thead>
						<tr>
							<th>&boxdr;</th>
							<?php for($x = 0; $x < $selected_spritesheet_info['num_sprites_x']; $x++): ?>
							<th><?=$x;?></th>
							<?php endfor; ?>
						</tr>
					</thead>
					<tbody>
						<?php for($y = 0; $y < $selected_spritesheet_info['num_sprites_y']; $y++): ?>
						<tr>
							<th valign="center"><?=$y;?></th>
							<?php for($x = 0; $x < $selected_spritesheet_info['num_sprites_x']; $x++):
								// math for sprite css
								$position_left = (($selected_spritesheet['offset_x'] + ($x * ($selected_spritesheet['sprite_width'] + $selected_spritesheet['padding_x']))) * ($stretched_image_width / $selected_spritesheet_info['width']));   // how many source pixels from the left side
								$position_top = (($selected_spritesheet['offset_y'] + ($y * ($selected_spritesheet['sprite_height'] + $selected_spritesheet['padding_y']))) * ($stretched_image_height / $selected_spritesheet_info['height']));   // how many source pixels from the top side
							?>
							<td valign="top" class="sprite_editor" id="sprite_<?=$y;?>_<?=$x;?>" data-y="<?=$y;?>" data-x="<?=$x;?>">
								<div class="preview"><div class="preview_sprite" style="background-position: -<?=$position_left;?>px -<?=$position_top;?>px">&nbsp;</div></div>
								<div class="viewable view_standard">
									<div class="row sprite_key"><?=((isset($spritesheet_sprites[ $y ][ $x ]['key']) && ("" != $spritesheet_sprites[ $y ][ $x ]['key']))?$spritesheet_sprites[ $y ][ $x ]['key']:"&nbsp;");?></div>
									<div class="row"><a href="#" class="show_view" data-view="edit" data-view-focus=".sprite_key">edit</a></div>
									<?php /*<div class="row"><a href="#" class="">test</a></div>*/ ?>
								</div>
								<div class="viewable view_edit view__hidden">
									<form method="post" class="sprite_edit_form" data-y="<?=$y;?>" data-x="<?=$x;?>">
									<input type="hidden" name="action" value="set_sprite" />
									<input type="hidden" name="spritesheet" value="<?=$selected_spritesheet['id'];?>" />
									<input type="hidden" name="offset_x" value="<?=$x;?>" />
									<input type="hidden" name="offset_y" value="<?=$y;?>" />
									<div class="row"><input type="text" name="sprite_key" value="<?=((isset($spritesheet_sprites[ $y ][ $x ]['key']))?$spritesheet_sprites[ $y ][ $x ]['key']:"");?>" placeholder="Key" <?php if(!empty($spritesheet_sprites[ $y ][ $x ]['ignore'])): ?> disabled="disabled" <?php endif; ?> class="sprite_key" /></div>
									<div class="row"><label for="ignore_sprite__<?=$y;?>_<?=$x;?>"><input type="checkbox" class="ignore_sprite" value="1" id="ignore_sprite__<?=$y;?>_<?=$x;?>" <?php if(!empty($spritesheet_sprites[ $y ][ $x ]['ignore'])): ?> checked="checked" <?php endif; ?> /> ignore</label></div>
									<div class="row"><input type="submit" value="Update" class="save_edit" /> <span class="status"></span></div>
									<div class="row"><a href="#" class="show_view" data-view="standard">cancel</a></div>
									</form>
								</div>
							</td>
							<?php endfor; ?>
						</tr>
						<?php endfor; ?>
					</tbody>
				</table>
				<style type="text/css">
					#content .sprite_editor .preview .preview_sprite { width: <?=$preview_width;?>px; height: <?=$preview_height;?>px; background-image: url('<?=URL_SPRITESHEET . $spritesheet['filename'];?>'); background-size: <?=$stretched_image_width;?>px <?=$stretched_image_height;?>px; }
				</style>
				<?php else: ?>
				<div class="message message_no_collapse">Please update the spritesheet settings to reveal the sprite table</div>
				<?php endif; ?>
				
				
				<?php  ?>
				<hr />
				<pre><?=print_r(array(
					'selected_spritesheet'		=> $selected_spritesheet,
					'selected_spritesheet_info'	=> $selected_spritesheet_info,
					'spritesheet_sprites'		=> $spritesheet_sprites,
				), true);?></pre>
				<?php  ?>
				
				
				
			<?php else: ?>
				<h1 class="header">Select a Spritesheet</h1>
				<p>Please select a spritesheet from the menu to begin</p>
			<?php endif; ?>
		</main>
	</div>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script type="text/javascript">
		<?=file_get_contents(DIR_BASE ."script.js");?>
	</script>
</body>
</html>