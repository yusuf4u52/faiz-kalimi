<?php
class Event_join extends grocery_CRUD_Model
{
	function get_list()
	{
		if ($this->table_name === null)
			return false;

		$select = "{$this->table_name}.*";

		// ADD YOUR SELECT FROM JOIN HERE <------------------------------------------------------
		$select .= ", thalilist.wingflat, thalilist.society";

		if (!empty($this->relation))
			foreach ($this->relation as $relation) {
				list($field_name, $related_table, $related_field_title) = $relation;
				$unique_join_name = $this->_unique_join_name($field_name);
				$unique_field_name = $this->_unique_field_name($field_name);

				if (strstr($related_field_title, '{'))
					$select .= ", CONCAT('" . str_replace(array('{', '}'), array("',COALESCE({$unique_join_name}.", ", ''),'"), str_replace("'", "\\'", $related_field_title)) . "') as $unique_field_name";
				else
					$select .= ", $unique_join_name.$related_field_title as $unique_field_name";

				if ($this->field_exists($related_field_title))
					$select .= ", {$this->table_name}.$related_field_title as '{$this->table_name}.$related_field_title'";
			}


		$this->db->select($select, false);

		// ADD YOUR JOIN HERE for example: <------------------------------------------------------
		$this->db->join('thalilist', 'thalilist.id = event_response.thaliid');

		$results = $this->db->get($this->table_name)->result();
		return $results;
	}
}
