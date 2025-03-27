<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Examples extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');

		$this->load->library('grocery_CRUD');
	}

	public function _example_output($output = null)
	{
		$this->load->view('example.php', $output);
	}

	public function offices()
	{
		$output = $this->grocery_crud->render();

		$this->_example_output($output);
	}

	function faiz()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('colombowalat@gmail.com','tinwalaabizer@gmail.com', 'mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('thalilist');
		$crud->unset_fields(array('Total_Pending'));
		$output = $crud->render();

		$this->_example_output($output);
	}

	function transporter_count()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('tzabuawala@gmail.com', 'mulla.moiz@gmail.com', 'yusuf4u52@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('transporter_daily_count');
		$crud->columns('id', 'date', 'name', 'count');
		$output = $crud->render();

		$this->_example_output($output);
	}

	function notpickedup()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('not_picked_up');

		$output = $crud->render();

		$this->_example_output($output);
	}

	function daily_hisab_items()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('daily_hisab_items');

		$output = $crud->render();

		$this->_example_output($output);
	}

	function daily_menu_count()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('daily_hisab');

		$output = $crud->render();

		$this->_example_output($output);
	}

	function payments()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('account');
		$crud->unset_edit();
		$crud->unset_delete();
		$crud->unset_clone();

		$output = $crud->render();

		$this->_example_output($output);
	}

	function sf_hisab()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('sf_hisab');

		$output = $crud->render();

		$this->_example_output($output);
	}

	function receipts()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_table('receipts');
		$crud->unset_edit();
		$crud->unset_delete();
		$crud->unset_clone();
		$output = $crud->render();

		$this->_example_output($output);
	}

	function change()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else {
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");
		}

		$crud = new grocery_CRUD();
		$crud->columns('id', 'Thali', 'Operation', 'Date', 'datetime');
		$crud->set_table('change_table');
		$crud->unset_edit();
		$crud->unset_delete();
		$crud->unset_add();
		$output = $crud->render();
		$this->_example_output($output);
	}

	function event_response()
	{
		session_start();
		if (!is_null($_SESSION['fromLogin']) && in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com'))) {
		} else
			header("Location: http://kalimijamaatpoona.org/fmb/users/login.php");

		$crud = new grocery_CRUD();
		$crud->set_model('Event_join');
		$crud->set_table('event_response');
		$crud->columns('thalino', 'eventid', 'response', 'name', 'CONTACT', 'thalisize', 'wingflat', 'society', 'Full_Address', 'sector', 'subsector', 'Transporter', 'tiffinno', 'thali_size', 'ITS_No');
		$crud->where('eventid in (select id from events where showInAdmin=1)');
		$crud->unset_read();
		$crud->unset_edit();
		$crud->unset_delete();
		$output = $crud->render();
		$this->_example_output($output);
	}

	public function index()
	{
		$this->_example_output((object)array('output' => '', 'js_files' => array(), 'css_files' => array()));
	}


	public function valueToEuro($value, $row)
	{
		return $value . ' &euro;';
	}
}
