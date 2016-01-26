# Protocol Buffer for PHP #

Implementing the Google "Protocol Buffer" for PHP, include parsing ...

At the moment this project has an beta status. I completed the parser for the proto files and implemented Int, Enum, String and Nested Messages at the moment, for the message reading and writing. Feel free to complete the implementation.

If you wanna make some notes so just write a comment at http://coderpeek.com/2008-07-17-protocol-buffer-for-php#comments


## Quick Start & Tutorial ##

To create the php classes out of a proto file just do this

```
require_once('../parser/pb_parser.php');
$test = new PBParser();
$test->parse('./performance.proto');
```

for all other see http://coderpeek.com/2008-07-17-protocol-buffer-for-php