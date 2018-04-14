<?php
	function get_results($query, $key=false) {
		if(empty($query)) {
			return array();
		}
		
		$result = mysql_query($query) or die("get_results: ". mysql_error());
		
		$rows = array();
		
		if(@mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_assoc($result)) {
				if((false !== $key) && isset($row[ $key ])) {
					$rows[ $row[ $key ] ] = $row;
				} else {
					$rows[] = $row;
				}
			}
		}
		
		return $rows;
	}
	
	function get_row($query) {
		return @array_shift(get_results($query));
	}
	
	function get_col($query, $col, $key=false) {
		if(empty($query)) {
			return array();
		}
		
		$result = mysql_query($query) or die("get_col: ". mysql_error());
		
		$rows = array();
		
		if(@mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_assoc($result)) {
				if(isset($row[ $col ])) {
					$value = $row[ $col ];
				} else {
					$value = @array_shift(array_values($row));
				}
				
				if((false !== $key) && isset($row[ $key ])) {
					$rows[ $row[ $key ] ] = $value;
				} else {
					$rows[] = $value;
				}
			}
		}
		
		return $rows;
	}
	
	function get_var($query, $col=false) {
		$row = get_row($query);
		
		if(empty($row) || is_null($row)) {
			return null;
		}
		
		if(false !== $col) {
			if(isset($row[ $col ])) {
				return $row[ $col ];
			}
			
			return null;
		}
		
		return array_shift($row);
	}
	
	function esc_sql($string) {
		return mysql_real_escape_string($string);
	}