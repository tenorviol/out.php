<?php
/**
 * out.php - Terse output functions for php (echo is evil)
 * http://github.com/tenorviol/out.php
 *
 * Copyright (C) 2012 Christopher Johnson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace out;

use InvalidArgumentException;

const REPLACEMENT_CHARACTER = "\xEF\xBF\xBD";

if (defined('ENT_SUBSTITUTE')) {

  function text($s) {
    $s = replace_control_characters($s);
    echo htmlentities($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

} else {

  function text($s) {
    $s = replace_control_characters($s);
    $s = replace_non_utf8($s);
    echo htmlentities($s, ENT_QUOTES, 'UTF-8');
  }

}

function raw($s) {
  $s = replace_non_utf8($s);
  echo $s;
}

function binary($s) {
  echo $s;
}

function script($s) {
  if (strpos($s, '</script') !== false) {
    throw new InvalidArgumentException('HTML script elements cannot contain "</script"');
  }
  raw($s);
}

function style($s) {
  if (strpos($s, '</style') !== false) {
    throw new InvalidArgumentException('HTML style elements cannot contain "</style"');
  }
  raw($s);
}

function cdata($s) {
  if (strpos($s, ']]>') !== false) {
    throw new InvalidArgumentException('CDATA elements cannot contain "]]>"');
  }
  raw($s);
}

function replace_non_utf8($s) {
  $re = '/((  [\x00-\x7F]                   # 1-byte   0xxxxxxx
            | [\xC2-\xDF][\x80-\xBF]        # 2-bytes  110xxxMx 10xxxxxx
            | \xE0[\xA0-\xBF][\x80-\xBF]    # 3-bytes  11100000 10Mxxxxx 10xxxxxx
            | [\xE1-\xEC][\x80-\xBF]{2}     # 3-bytes  1110xxxM 10xxxxxx 10xxxxxx
            | \xED[\x80-\x9F][\x80-\xBF]    # 3-bytes  11101101 100xxxxx 10xxxxxx
            | [\xEE-\xEF][\x80-\xBF]{2}     # 3-bytes  1110111x 10xxxxxx 10xxxxxx
            | \xF0[\x90-\xBF][\x80-\xBF]{2} # 4-bytes  11110000 10xMxxxx 10xxxxxx 10xxxxxx
            | [\xF1-\xF3][\x80-\xBF]{3}     # 4-bytes  111100xx 10xxxxxx 10xxxxxx 10xxxxxx
            | \xF4[\x80-\x8F][\x80-\xBF]{2} # 4-bytes  11110100 1000xxxx 10xxxxxx 10xxxxxx
          )+)
          | .
        /x';
  return preg_replace_callback($re, function ($matches) {
    if (isset($matches[1][0])) {
      return $matches[1];  // valid character(s)
    } else {
      return REPLACEMENT_CHARACTER;
    }
  }, $s);
}

function replace_control_characters($s) {
  $re = '/( [\x00-\x08]
          |  \x0B
          | [\x0E-\x1F]
          |  \x7F
          |  \xC2[\x80-\x9F]
          |  \xEF\xB7[\x90-\xAF]                  # U+FDD0 to U+FDEF
          |  \xEF\xBF[\xBE\xBF]                   # U+FFFE, U+FFFF
          |  \xF0      [\x9F-\xBF]\xBF[\xBE\xBF]  # U+[1-3]FFF[EF]
          | [\xF1-\xF3][\x8F-\xBF]\xBF[\xBE\xBF]  # U+[4-F]FFF[EF]
          |  \xF4       \x8F      \xBF[\xBE\xBF]  # U+10FFF[EF]
          )/x';
  return preg_replace($re, REPLACEMENT_CHARACTER, $s);
}
