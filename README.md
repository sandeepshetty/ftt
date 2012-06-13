# retest.php

Drop-in function testing and auto-documentation library.


## Requirements



## Getting Started

### Download
Download the [latest version of retest.php](https://github.com/sandeepshetty/retest.php/archives/master):

```shell
$ curl -L http://github.com/sandeepshetty/retest.php/tarball/master | tar xvz
$ mv sandeepshetty-retest.php-* retest.php
```

### Usage

To auto-refresh every few seconds, add the query paramter `refresh_in`. For example, to auto refresh every 5 seconds:

```
http://example.com/dir/to/test/retest.php?refresh_in=5
```