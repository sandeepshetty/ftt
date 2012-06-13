# ftt.php

Single-file drop-in Function Testing Tool (ftt) and auto-documenter


## Requirements



## Getting Started

### Download
Download the [latest version of ftt.php](https://github.com/sandeepshetty/ftt.php/archives/master):

```shell
$ curl -L http://github.com/sandeepshetty/ftt.php/tarball/master | tar xvz
$ mv sandeepshetty-ftt.php-* ftt.php
```

### Usage


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
	}

?>
```

Run the test:

```
http://localhost/path/to/hello.php/ftt.php
```

To auto-refresh every few seconds, add the query paramter `refresh_in`. For example, to auto refresh every 5 seconds:

```
http://example.com/dir/to/test/ftt.php?refresh_in=5
```