<?php
public function get_open($i)
{
	return DB::fetch_all('SELECT gallery,gallery_name FROM %t WHERE open=%d',array($this->table,$i));
}

?>