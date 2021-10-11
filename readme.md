# pp - a php parser combinator

this is a little research project inspired by this excellent youtube series by [low level javascript](https://www.youtube.com/watch?v=6oQLRhw5Ah0&list=PLP29wDx6QmW5yfO1LAgO8kU3aQEj8SIrU&index=1).

elf parser
```
$ php src/run.php false.i386
Array
(
    [header] => 7fELF
    [plattform] => 32bit
    [endianess] => little endian
    [version] => 1
    [os ( mostly wrong )] => System V
    [ABI Version] => 0
    [unsed] =>
    [object type] => ET_DYN
    [architecture] => x86
)
```

ipv4 packet parser
```
$ php src/ip4.php
Array
(
    [Version] => 4
    [IHL] => 5
    [DSCP] => 0
    [ECN] => 0
    [Total Length] => 68
    [Identification] => 44299
    [Flags] => 0
    [Fragment Offset] => 0
    [TTL] => 64
    [Protocol] => 17
    [Header Checksum] => 29298
    [Source Ip] => 172.20.2.253
    [Destination Ip] => 172.20.0.6
)
```

polish notation evaluation
```
$ php src/polish.php "(+ (+ (* 1 1000) (* 33 10)) 7)"                                                                                                                                                                                                                                    47ms » ✓
array(2) {
  ["type"]=>
  string(9) "operation"
  ["value"]=>
  array(3) {
    ["op"]=>
    string(1) "+"
    ["a"]=>
    array(2) {
      ["type"]=>
      string(9) "operation"
      ["value"]=>
      array(3) {
        ["op"]=>
        string(1) "+"
        ["a"]=>
        array(2) {
          ["type"]=>
          string(9) "operation"
          ["value"]=>
          array(3) {
            ["op"]=>
            string(1) "*"
            ["a"]=>
            array(2) {
              ["type"]=>
              string(6) "number"
              ["value"]=>
              string(1) "1"
            }
            ["b"]=>
            array(2) {
              ["type"]=>
              string(6) "number"
              ["value"]=>
              string(4) "1000"
            }
          }
        }
        ["b"]=>
        array(2) {
          ["type"]=>
          string(9) "operation"
          ["value"]=>
          array(3) {
            ["op"]=>
            string(1) "*"
            ["a"]=>
            array(2) {
              ["type"]=>
              string(6) "number"
              ["value"]=>
              string(2) "33"
            }
            ["b"]=>
            array(2) {
              ["type"]=>
              string(6) "number"
              ["value"]=>
              string(2) "10"
            }
          }
        }
      }
    }
    ["b"]=>
    array(2) {
      ["type"]=>
      string(6) "number"
      ["value"]=>
      string(1) "7"
    }
  }
}
float(1337)
```