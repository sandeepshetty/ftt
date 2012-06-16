<?php

	function test_function_being_tested()
	{
		should_return('foobar', when_passed('test_foobar'));
		should_return('foobar', when_passed('foobar'));
		//should_return(false, when_passed('foobar'));
	}

	function test_file_being_tested()
	{
		should_return('foobar.php', when_passed('foobar.test.php'));
	}


	function test_when_passed()
	{
		should_return(array('foo', 'bar'), when_passed('foo', 'bar'));
	}

?>