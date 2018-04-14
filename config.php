<?php
	@mysql_connect("localhost", "root", "468f6g") or die(mysql_error());
	@mysql_select_db("dev_sprite_describer") or die(mysql_error());
	@mysql_set_charset("utf8");
	
	define('DIR_BASE', __DIR__ ."/");
	define('DIR_SPRITESHEET', DIR_BASE ."sheets/");
	
	define('URL_BASE', rtrim(preg_replace("#\/([^/]+)\.php(?:.*)$#si", "/", getenv("SCRIPT_NAME")), "/") ."/");
	define('URL_SPRITESHEET', URL_BASE ."sheets/");
	
	require_once(__DIR__ ."/inc_db.php");