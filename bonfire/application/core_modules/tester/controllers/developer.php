<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Developer extends Admin_Controller {

	public function __construct() 
	{
		parent::__construct();
		
		Template::set('toolbar_title', 'Unit Tester');
	}
	
	//--------------------------------------------------------------------
	

	public function index() 
	{
		Template::set('test_map', module_files(null, 'tests'));
	
		Template::render();
	}
	
	//--------------------------------------------------------------------
	
	/*
		Runs the tests and displays the results.
	*/
	public function run() 
	{
		if ($this->input->post('submit') && !$this->input->post('tests'))
		{
			Template::set_message('Please select one or more modules to run tests on.', 'attention');
			redirect('admin/developer/tester');
		}
		
		$this->load->library('unit_test');
		$this->load->library('Unit_tester');
		
		$this->unit->set_test_items(array('test_name', 'result', 'notes'));
		
		$vars = array();	
		$vars['results']	= array();	// Someplace to store the results of all of our tests.
		
		$modules = is_array($this->input->post('tests')) ? $this->input->post('tests') : array($this->input->post('tests'));
		
		// Send to view for reload
		Template::set('test_names', $modules);
		Template::set_block('sub_nav', 'developer/_sub_nav');
		
		// Run through each module, running their tests.
		foreach ($modules as $module)
		{
			$tests = module_files($module, 'tests');
			$tests = $tests[$module]['tests'];
			
			// Run all of the tests.
			foreach ($tests as $test)
			{
				// We need to make sure it's not a sql file!
				if (strpos($test, '.sql') === false)
				{
					// Grab our test class			
					$test_class = str_replace(EXT, '', end(explode('/', $test)));
					$module_file_path = module_file_path($module, 'tests', $test);
					require($module_file_path);
					
					$class = new $test_class;
					
					// Tell it what module it's running. 
					// (Saves us from manually doing it for every test class)
					$class->set_module_path(dirname($module_file_path));
					
					// Clear previous unit results
					$this->unit->reset();
					
					// Run the tests
					$class->run_all();
					
					// Store our results for processing later.
					$vars['results'][$module. ' : <b>'. ucwords(str_replace('_', ' ', $test_class)) .'</b>'] = array(
						'report'	=> $this->unit->report(),
						'raw'		=> $this->unit->result(),
						'passed'	=> 0,
						'failed'	=> 0
					);
				}
			}
		}
		
		// Find our totals
		$vars['total_passed']	= 0;
		$vars['total_failed']	= 0;
		
	
		if (count($vars['results']))
		{	
			foreach ($vars['results'] as $key => $result)
			{
				foreach ($result['raw'] as $k => $v)
				{	
					// We're not using the results so strip it.
					unset($vars['results'][$key]['report']);
				
					if (isset($v['Result']))
					{	
						if (strtolower($v['Result']) == 'passed')
						{
							$vars['total_passed']++;
							$vars['results'][$key]['passed']++;
							
							//print_r($vars['results'][$key]);
						}
						else
						{
							$vars['total_failed']++;
							$vars['results'][$key]['failed']++;
						}
					}
				}
			}
		}

		Template::set($vars);
	
		// display the results
		Template::render();
	}
	
	//--------------------------------------------------------------------
	
}