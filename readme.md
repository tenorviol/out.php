out.php
=======

`echo` is evil. It is the exact right way to dump binary data to the output stream.
Don't use it. Use one of these instead:

* `out\text($s)` to write html-encoded text
* `out\raw($s)` to write text as-is, including html or whatever
* `out\binary($s)` to echo binary data
* `out\script($s)` to write raw text into a script element
* `out\style($s)` to write raw text into a style element
* `out\cdata($s)` to write raw text into a cdata element

All functions except binary enforce strict UTF-8 compliance,
replacing non-utf8 characters with the unicode replacement character U+FFFD
