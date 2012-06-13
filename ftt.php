<?php

	ftt_include_files();
	ftt_call_test_functions();


	function should_return($expected_return_value, $params=NULL, $msg=NULL)
	{
		$debug_backtrace = debug_backtrace();
		$function = function_being_tested($debug_backtrace[1]['function']);

		if (!$function) return trigger_error("should_return() can only be called inside a test function");
		if (!function_exists($function)) return trigger_error("Function $function does not exist");

		$returned_value = call_user_func_array($function, $params);
		ftt_incr('assertions');

		$is_expectation_met = ($returned_value === $expected_return_value);
		ftt_assertions(ftt_assertion($function, $expected_return_value, $returned_value, $params, $msg, $debug_backtrace));

		if (!$is_expectation_met)
		{
			ftt_incr('failures');
			ftt_failures(ftt_assertion($function, $expected_return_value, $returned_value, $params, $msg, $debug_backtrace));
		}

		return $is_expectation_met;
	}

		function ftt_assertion($function, $expected_return_value, $returned_value, $params, $msg, $debug_backtrace)
		{
			return array
			(
				'function' => $function,
				'location' => assertion_location($debug_backtrace),
				'message' => ftt_assert_description($function, $expected_return_value, $returned_value, $params, $msg)
			);
		}


	function when_passed()
	{
		return func_get_args();
	}



		function function_being_tested($test_function)
		{
			return preg_replace('/^test_/', '', $test_function);
		}

		function ftt_assert_description($function, $expected_return_value, $returned_value, $passed_arguments, $msg)
		{
			$is_expectation_met = ($returned_value === $expected_return_value);
			if ($is_expectation_met)
			{
				$function_call = "<strong>$function</strong>".'('.ftt_array_to_argument_list($passed_arguments).')';
				//TODO: reason for %1\$s instead of %s: the $f in $function_call kicks in argument swaping in sprintf :(
				$msg = is_null($msg) ? sprintf("$function_call returns <em>%1\$s</em>", htmlspecialchars(var_export($returned_value, true))) : $msg;
			}
			else
			{
				$function_call = $function.'('.ftt_array_to_argument_list($passed_arguments).')';
				//TODO: reason for %1\$s instead of %s: the $f in $function_call kicks in argument swaping in sprintf :(
				$msg = is_null($msg) ? sprintf("<strong>$function_call</strong> should have returned <strong>%1\$s</strong> but was <strong>%2\$s</strong>", htmlspecialchars(var_export($expected_return_value, true)), htmlspecialchars(var_export($returned_value, true))) : $msg;
			}

			return $msg;
		}

			function ftt_array_to_argument_list($arguments)
			{
				$argument_list = '';

				if (is_array($arguments))
				{
					$arguments = array_map('ftt_variable_to_string', $arguments);
					$argument_list = implode(', ', $arguments);
				}

				return $argument_list;
			}
				function ftt_variable_to_string($argument)
				{
					return htmlspecialchars(var_export($argument, true));
				}

		function ftt_assertions($assertion=NULL)
		{
			static $assertions=array();
			if (is_null($assertion)) return $assertions;

			$assertions[$assertion['function']][] = array('location' => $assertion['location'],
			                                              'message' => $assertion['message']);
			return $assertions;

		}

		function ftt_failures($assertion=NULL)
		{
			static $assertions;

			$assertions = isset($assertions) ? $assertions : array();
			if (is_null($assertion)) return $assertions;

			$assertions[$assertion['function']][] = array('location' => $assertion['location'],
			                                              'message' => $assertion['message']);
			return $assertions;
		}

		function assertion_location($debug_backtrace)
		{
			return array('file'=>$debug_backtrace[0]['file'], 'line'=>$debug_backtrace[0]['line']);
		}



		function ftt_include_files()
		{
			foreach (ftt_test_files() as $test_file)
			{
				if ($source_file = ftt_source_file($test_file))
				{
					include_once $source_file;
					ftt_incr('source_files');
					ftt_source_files($source_file);
				}

				include_once $test_file;
				ftt_incr('test_files');
			}
		}

			function ftt_test_files()
			{
				return ftt_globr(dirname(__FILE__), '*.test.php');
			}
				function ftt_globr($dir, $pattern)
				{
					$files = glob($dir.DIRECTORY_SEPARATOR.$pattern);
					foreach (glob($dir.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) as $dirname)
					{
						$files = array_merge($files, ftt_globr($dirname, $pattern));
					}

					return $files;
				}

			function ftt_source_file($test_file)
			{
				$source_file = file_being_tested($test_file);

				if (file_exists($source_file))
				{
					return $source_file;
				}
				else
				{
					$source_file = preg_replace('{'.addslashes(DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR).'}',
												DIRECTORY_SEPARATOR,
												$source_file);
					if (file_exists($source_file)) return $source_file;
				}

				return false;
			}
				function file_being_tested($test_file)
				{
					return preg_replace('/\.test\.php$/', '.php', $test_file);
				}

			function ftt_incr($counter_name)
			{
				return ftt_counter($counter_name, true);
			}

				function ftt_counter($counter_name, $increment=false)
				{
					static $counters;
					if (!isset($counters[$counter_name])) $counters[$counter_name] = 0;
					if ($increment) $counters[$counter_name]++;
					return $counters[$counter_name];
				}

		function ftt_source_files($source_file=NULL)
		{
			static $source_files=array();
			if (is_null($source_file)) return $source_files;
			$source_files[] = $source_file;
			return $source_files;
		}

		function ftt_call_test_functions()
		{
			ftt_source_coverage('start');

			foreach (ftt_test_functions() as $test_function)
			{
				ftt_incr('tests');
				$test_function();
			}

			ftt_source_coverage('stop');
		}
			function ftt_test_functions()
			{
				$all_defined_functions = get_defined_functions();
				$user_defined_functions = $all_defined_functions['user'];
				return array_filter($user_defined_functions, 'restest_is_test_function');
			}
				function restest_is_test_function($function)
				{
					return preg_match('/^test_.*/', $function);
				}

			function ftt_source_coverage($op='')
			{
				static $source_coverage=array();

				if ('start' == $op and function_exists('xdebug_start_code_coverage'))
				{
					xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
				}

				if ('stop' == $op and function_exists('xdebug_get_code_coverage'))
				{
					$source_coverage = xdebug_get_code_coverage();
					$source_coverage = ftt_filter_code_coverage($source_coverage);
				}

				return $source_coverage;
			}

				function ftt_filter_code_coverage($source_coverage)
				{
					//TODO: rename files created in tests to testfoo... so that they can be filtered out
					foreach ($source_coverage as $file=>$file_coverage)
					{
						if ((substr($file, -9) == '.test.php') or
						    (substr($file, -10) == 'retest.php') or
						    (substr($file, -15) == 'retest.test.php'))
							unset($source_coverage[$file]);
						else $source_coverage[$file] = array_filter($file_coverage, 'ftt_is_negative_value');
					}

					foreach ($source_coverage as $file=>$file_coverage)
					{
						if (file_exists($file))
						{
							$source = file($file);
							foreach ($file_coverage as $line_number=>$value)
							{
								if ((trim($source[($line_number-1)]) == '}') or (trim($source[($line_number-1)]) == '{'))
									unset($source_coverage[$file][$line_number]);
								else $source_coverage[$file][$line_number] = $source[($line_number-1)];
							}
						}
					}

					$source_coverage = array_filter($source_coverage, create_function('$val', 'return !empty($val);'));

					return $source_coverage;
				}
					function ftt_is_negative_value($val)
					{
						return $val < 0;
					}

			function ftt_count_of_untested_lines($source_coverage)
			{
				return array_reduce($source_coverage, 'ftt_accumulator', 0);
			}
				function ftt_accumulator($counter, $value)
				{
					return $counter += count($value);
				}

?>


<?php //HTML helper

	function ftt_meta_refresh($seconds=NULL)
	{
		if (!is_null($seconds))
		{
			return '<meta http-equiv="Refresh" content="'.$seconds.'; url=retest.php?refresh_in='.$seconds.'" />';
		}
	}

	function ftt_no_tests()
	{
		return (count(ftt_test_functions()) == 0);
	}


	function ftt_status_red()
	{
		return (ftt_counter('failures') > 0);
	}


	function ftt_red_or_green_bar()
	{
		return (ftt_status_red()) ? "red-bar" : "green-bar";
	}

	function ftt_pluralize($str, $no)
	{
		return (1 !== $no) ? $str.'s' : $str;
	}

	function ftt_test_filter($test)
	{
		$test = preg_replace('/([A-Z])/', ' \1', $test);
		$test = preg_replace('/^test_/', '', $test);
		$test = strtolower($test);
		return $test;
//		return strtr(htmlentities($test), array('&shy;'=>'-'));
	}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3c.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<!-- meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /-->
	<?php if (isset($_GET['refresh_in'])) echo ftt_meta_refresh(htmlspecialchars($_GET['refresh_in'])); ?>
	<title>Retest - Red/Green/Refactor</title>
	<style type="text/css">

		body {
			margin: 0;
			padding: 10px 15px;
			font-family: 'lucida grande', 'lucida sans unicode', lucida, verdana, geneva, sans-serif;
			//font-size: 76%;
			font-size: 190%;
			background-color: #fff;
			color: #000000;
		}

		div#container {
			padding: 0;
			font-size: 1.1em;
		}

		div#header {
			padding: 10px 15px 12px 15px;
			margin: 0;
//			background-color: #433C2A;
//			background-color: #BFDAE1;
			background-color: #EFCEB3;
			color: #fff;
		}

		h1 {
			font: 2em trebuchet ms, verdana;
			margin: 0;
		}

		div#header a {
			text-decoration: none;
			color: #fff;
		}

		div#result-bar {
			margin: 10px 0;
			padding: 10px 15px;
		}
		.red-bar { color: #fff; padding: 5px 8px; background-color: red;}
		.green-bar { color: #fff; padding: 5px 8px; background-color: #90B500; }
		.grey-bar { color: #fff; padding: 5px 8px; background-color: #999;}

		div#failures, div#behaviours, div#tests, div#test-files {
			margin: 10px 0 10px 2em;
			padding: 10px 15px;
//			background-color: #DAEAED;
			background-color: #EFEFEF;
			border: 1px solid #E0E0E0;
		}

		div#info, div#tdd, div#code-coverage {
			margin: 10px 0;
			padding: 10px 15px;
//			background-color: #DAEAED;
			background-color: #EFEFEF;
//			border: 1px solid #E0E0E0;
			color: #666;
		}

.frontcolumn {
/*	width: 30%;*/
	float: left;
	padding-right: 3%;
}

		div.status {
			float: right;
			margin: 0;
			padding: 4px 4px 0 0;
		}

		ul.info {
			margin: 0.5em 0 0 0;
			padding: 0;
		}

		.info li {
			margin: 0 0 0.7em 0;
			padding: 0;
			clear: both;
			list-style-type: none;
		}
		.info li a { font-weight: normal; }

		.num {
			background: #eee;
			border-right: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
			float: left;
			text-align: center;
			margin: 0 5px 5px 0;
			line-height: 1.1em;
			padding: 2px;
			font-size: 11px;
			width: 3em;
			white-space: nowrap;
			color: #666;
			font-weight: bold;
		}

		.cc {
			font-size: 0.8em;
		}
	</style>
	<script language="javascript">

		function toggle($element)
		{
			if(document.getElementById($element).style.display == "block")
				document.getElementById($element).style.display = "none";
			else
				document.getElementById($element).style.display = "block";
		}

	</script>

</head>
<body>

<div id='container'>

	<div id="result-bar" class="<?php echo ftt_red_or_green_bar(); ?>">
		<?php if (ftt_no_tests()) { ?>
			Write a test!
		<?php } elseif (ftt_status_red()) { ?>
			<strong>It failed!</strong>
		<?php } else { ?>
			<strong>It worked!</strong>
		<?php } ?>
	</div>

	<div id="info">
		<ul class="info">
		<?php if (ftt_status_red()) { ?>
			<li><strong><?php echo ftt_counter('failures') ?></strong> <a href="javascript:toggle('failures');"><?php echo ftt_pluralize('Failure', ftt_counter('failures')) ?></a>
					<div id="failures" style="display: block">
					<?php
						echo "<ul>";
						if (ftt_counter('failures') > 0)
						{
							foreach (ftt_failures() as $function=>$details)
							{
								foreach ($details as $detail)
								{
									echo "<li>{$detail['message']} [in {$detail['location']['file']} on line {$detail['location']['line']}]</li>";
								}
							}
						}
						echo "</ul>";
					?>
					</div>
			</li>
			<?php } ?>
			<li><strong><?php echo ftt_counter('tests') ?></strong> <a href="javascript:toggle('tests');"><?php echo ftt_pluralize('Test', ftt_counter('tests')) ?></a>
				<div id="tests" style="display: none;">
					<?php
						echo "<ul>";
						$all_assertions = ftt_assertions();
						foreach (ftt_test_functions() as $test)
						{
							$function = ftt_test_filter($test);
							$test_assertions = isset($all_assertions[$function]) ? $all_assertions[$function] : array();
							echo "<li>$function";
							echo '<div style="font-size: 0.6em; font-family: monospace;">';
							foreach ($test_assertions as $test_assertion)
							{
								echo $test_assertion['message']."<br />";
							}
							echo "</div>";
							echo "</li>";
						}

					//~ $id = md5($file);
					//~ echo "<li class=\"cc\"><a href=\"javascript:toggle('{$id}-code-coverage');\">{$file}</a>";
					//~ echo "<div id=\"{$id}-code-coverage\" style=\"display: none\">";
					//~ foreach ($lines_not_covered as $line_number=>$status)
					//~ {
						//~ echo "Line # $line_number :<code>$status</code><br />";
					//~ }
					//~ echo "</div>";
					//~ echo "</li>";

						echo "</ul>";
					?>
				</div>
			</li>
			<li><strong><?php echo ftt_counter('test_files') ?></strong> <a href="javascript:toggle('test-files');">Test <?php echo ftt_pluralize('File', ftt_counter('test_files')) ?></a>
				<div id="test-files" style="display: none">
					<?php
						echo "<ul>";
						foreach (ftt_test_files() as $test_file)
						{
							echo "<li>$test_file</li>";
						}
						echo "</ul>";
					?>
				</div>
			</li>
			<li><strong><?php echo ftt_counter('source_files') ?></strong> <a href="javascript:toggle('source-files');">Source <?php echo ftt_pluralize('File', ftt_counter('source_files')) ?></a>
				<div id="source-files" style="display: none">
					<?php
						echo "<ul>";
						foreach (ftt_source_files() as $source_file)
						{
							echo "<li>$source_file</li>";
						}
						echo "</ul>";
					?>
				</div>
			</li>

		</ul>
	</div>
<!--
	<div id="code-coverage">
		<strong><?php echo ftt_count_of_untested_lines(ftt_source_coverage()) ?></strong> <a href="javascript:toggle('untested-code');">Source lines not covered by tests (requires xdebug)</a>
		<div id="untested-code" style="display: none">
			<?php
				echo "<ul>";
				foreach (ftt_source_coverage() as $file=>$lines_not_covered)
				{
					$id = md5($file);
					echo "<li class=\"cc\"><a href=\"javascript:toggle('{$id}-code-coverage');\">{$file}</a>";
					echo "<div id=\"{$id}-code-coverage\" style=\"display: none\">";
					foreach ($lines_not_covered as $line_number=>$status)
					{
						echo "Line # $line_number :<code>$status</code><br />";
					}
					echo "</div>";
					echo "</li>";
				}
				echo "</ul>";
			?>
		</div>
	</div>
-->
</div>

</body>
</html>
