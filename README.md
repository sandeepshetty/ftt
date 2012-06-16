# ftt.php

Single-file drop-in Function Testing Tool (ftt) and auto-documenter.


## Getting Started

### Download
Download the [latest version of ftt.php](https://github.com/sandeepshetty/ftt.php/archives/master):

```shell
$ curl -L http://github.com/sandeepshetty/ftt.php/tarball/master | tar xvz
$ mv sandeepshetty-ftt.php-* ftt.php
```


### Usage

* ftt is a function testing tool, that is, it is used to test regular functions purely based on their input and output.
* Just drop `ftt.php` into the top level source directory for which you want to write tests.
* Tests are written in files that have the same name as the source file except for the .test.php extension. For example, to test the functions inside `example.php`, create the file `example.test.php`. Test files can either reside in the same directory as the source file, or inside a sub-directory `tests` relative to the source file.
* Tests are just regular functions that have the name of the function they are testing prefixed with `test_`. For example, `test_foobar()` tests `foobar()`.
* Tests usally just contain one or more assertions of the form `should_return($expected_output, when_passed($some_input, $another_input));`.
* Currently, test can only be run via HTTP by accessing `ftt.php`. Feel free to contribute capabilities to run from CLI.


### Example
``` php
answer.php
<?php

	function answer()
	{
		return 42;
	}

?>
```

``` php
answer.test.php
<?php

	function test_answer()
	{
		should_return(42, when_passed('The Ultimate Question of Life, the Universe, and Everything'));
		should_return(42, when_passed('foobar'));
	}

?>
```

To run the test visit:

```
http://localhost/path/to/answer.php/ftt.php
```

To auto-refresh every few seconds, add the query paramter `refresh_in`. For example, to auto refresh every 5 seconds:

```
http://example.com/dir/to/test/ftt.php?refresh_in=5
```