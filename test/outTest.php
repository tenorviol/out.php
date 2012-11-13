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

require_once __DIR__.'/../out.php';

class outTest extends PHPUnit_Framework_TestCase {

  public function outProvider() {
    $R = out\REPLACEMENT_CHARACTER;
    return array(
      array('', 'text', ''),
      array('', 'raw', ''),
      array('', 'binary', ''),
      array('', 'script', ''),
      array('', 'style', ''),
      array('', 'cdata', ''),
      array("<>&'\" \x7F \xFF\n\xC0\x80 </script </style ]]> ", 'text',   "&lt;&gt;&amp;&#039;&quot; $R $R\n$R$R &lt;/script &lt;/style ]]&gt; "),
      array("<>&'\" \x7F \xFF\n\xC0\x80 </script </style ]]> ", 'raw',    "<>&'\" \x7F $R\n$R$R </script </style ]]> "),
      array("<>&'\" \x7F \xFF\n\xC0\x80 </script </style ]]> ", 'binary', "<>&'\" \x7F \xFF\n\xC0\x80 </script </style ]]> "),
      array("<>&'\" \x7F \xFF\n\xC0\x80 </scr ipt </style ]]>", 'script', "<>&'\" \x7F $R\n$R$R </scr ipt </style ]]>"),
      array("<>&'\" \x7F \xFF\n\xC0\x80 </script </st yle ]]>", 'style',  "<>&'\" \x7F $R\n$R$R </script </st yle ]]>"),
      array("<>&'\" \x7F \xFF\n\xC0\x80 </script </style ]] >", 'cdata',  "<>&'\" \x7F $R\n$R$R </script </style ]] >"),
    );
  }

  /**
   * @dataProvider outProvider
   */
  public function testOut($s, $func, $expect) {
    $outfunc = "out\\$func";
    ob_start();
    $outfunc($s);
    $result = ob_get_clean();
    $this->assertEquals($expect, $result);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testOutScriptThrowsOnInvalidInput() {
    out\script("\n\t</script ");
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testOutStyleThrowsOnInvalidInput() {
    out\style("\n\t</style ");
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testOutCdataThrowsOnInvalidInput() {
    out\cdata("\n\t]]> ");
  }

  public function utf8Provider() {
    $R = out\REPLACEMENT_CHARACTER;
    return array(

      // valid utf-8 sequences, all edge cases
      array("\x00"),
      array("\x7F"),
      array("\xC2\x80"),
      array("\xDF\xBF"),
      array("\xE0\xA0\x80"),
      array("\xED\x9F\xBF"),
      array("\xEE\x80\x80"),
      array("\xEF\xBF\xBF"),
      array("\xF0\x90\x80\x80"),
      array("\xF4\x8F\xBF\xBF"),

      // invalid utf-8 sequences
      // http://dev.w3.org/html5/spec/single-page.html#utf-8

      // One byte in the range FE to FF
      array("\xFE", "$R"),
      array("\xFF", "$R"),
      // Overlong forms (e.g. F0 80 80 A0)
      array("\xF0\x80\x80\xA0", "$R$R$R$R"),
      // One byte in the range C0 to C1, followed by one byte in the range 80 to BF (overlong)
      array("\xC0\x80", "$R$R"),
      array("\xC1\xBF", "$R$R"),
      // One byte in the range F0 to F4, followed by three bytes in the range 80 to BF that represent a code point above U+10FFFF
      array("\xF4\x90\x80\x80", "$R$R$R$R"),
      // One byte in the range F5 to F7, followed by three bytes in the range 80 to BF
      array("\xF5\x80\x80\x80", "$R$R$R$R"),
      array("\xF7\xBF\xBF\xBF", "$R$R$R$R"),
      // One byte in the range F8 to FB, followed by four bytes in the range 80 to BF
      array("\xF8\x80\x80\x80\x80", "$R$R$R$R$R"),
      array("\xFB\xBF\xBF\xBF\xBF", "$R$R$R$R$R"),
      // One byte in the range FC to FD, followed by five bytes in the range 80 to BF
      array("\xFC\x80\x80\x80\x80\x80", "$R$R$R$R$R$R"),
      array("\xFD\xBF\xBF\xBF\xBF\xBF", "$R$R$R$R$R$R"),
      // One byte in the range C0 to FD that is not followed by a byte in the range 80 to BF
      array("\xC0", "$R"),
      array("\xFD", "$R"),
      // One byte in the range E0 to FD, followed by a byte in the range 80 to BF that is not followed by a byte in the range 80 to BF
      array("\xE0\xBF ", "$R$R "),
      array("\xFD\x80 ", "$R$R "),
      // One byte in the range F0 to FD, followed by two bytes in the range 80 to BF, the last of which is not followed by a byte in the range 80 to BF
      array("\xF0\x90\x80 ", "$R$R$R "),
      array("\xFD\xBF\xBF ", "$R$R$R "),
      // One byte in the range F8 to FD, followed by three bytes in the range 80 to BF, the last of which is not followed by a byte in the range 80 to BF
      array("\xF8\x80\x80\x80 ", "$R$R$R$R "),
      array("\xFD\xBF\xBF\xBF ", "$R$R$R$R "),
      // One byte in the range FC to FD, followed by four bytes in the range 80 to BF, the last of which is not followed by a byte in the range 80 to BF
      array("\xFC\x80\x80\x80\x80 ", "$R$R$R$R$R "),
      array("\xFD\xBF\xBF\xBF\xBF ", "$R$R$R$R$R "),
      // Any byte sequence that represents a code point in the range U+D800 to U+DFFF
      // The whole matched sequence must be replaced by a single U+FFFD REPLACEMENT CHARACTER. (ignored)
      array("\xED\xA0\x80", "$R$R$R"),
      array("\xED\xBF\xBF", "$R$R$R"),
      // One byte in the range 80 to BF not preceded by a byte in the range 80 to FD
      array(" \x80 ", " $R "),
      array(" \xFD ", " $R "),
      // One byte in the range 80 to BF preceded by a byte that is part of a complete UTF-8 sequence that does not include this byte
      // One byte in the range 80 to BF preceded by a byte that is part of a sequence that has been replaced by a U+FFFD REPLACEMENT CHARACTER, either alone or as part of a sequence

      // UTF-8 attack against unpatched Microsoft Internet Information Server (IIS) 4 and IIS 5 servers
      array(
        "http://servername/scripts/..\xC0\xAF../winnt/system32/ cmd.exe",
        "http://servername/scripts/..$R$R../winnt/system32/ cmd.exe",
      ),

      // multi-line replacement
      array(
        "foo
        bar <\xED\xA0\x80script>
          alert(666)
        <\xED\xBF\xBF/script>",
        "foo
        bar <{$R}{$R}{$R}script>
          alert(666)
        <{$R}{$R}{$R}/script>",
      ),

      // lots of random language strings

      // (Japanese) If you do not enter the tiger's cave, you will not catch its cub.
      array("虎穴に入らずんば虎子を得ず。"),
      // (Traditional Chinese) Reading ten thousand books is not as useful as travelling ten thousand miles
      array("讀萬卷書不如行萬里路"),
      // (Korean) Don't try to cover the whole sky with the palm of your hand.
      array("손바닥으로 하늘을 가리려한다"),
      // (Thai) Red ants swarming on a bunch of mangos
      array("มดแดงแฝงพวงมะม่วง"),
      // (Tibetan) writing styles
      array("uchen: དབུ་ཅན་, umê: དབུ་མེད་"),
      // (Greek) Let no one untrained in geometry enter.
      array("ἀγεωμέτρητος μηδεὶς εἰσίτω"),
      // (Turkish) language
      array("Türkçe"),

      // http://www.columbia.edu/~fdc/utf8/
      array("¥ · £ · € · $ · ¢ · ₡ · ₢ · ₣ · ₤ · ₥ · ₦ · ₧ · ₨ · ₩ · ₪ · ₫ · ₭ · ₮ · ₯ · ₹"),
      // From the Anglo-Saxon Rune Poem (Rune version):
      array("ᚠᛇᚻ᛫ᛒᛦᚦ᛫ᚠᚱᚩᚠᚢᚱ᛫ᚠᛁᚱᚪ᛫ᚷᛖᚻᚹᛦᛚᚳᚢᛗ
ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ
ᚷᛁᚠ᛫ᚻᛖ᛫ᚹᛁᛚᛖ᛫ᚠᚩᚱ᛫ᛞᚱᛁᚻᛏᚾᛖ᛫ᛞᚩᛗᛖᛋ᛫ᚻᛚᛇᛏᚪᚾ᛬"),
      // From Laȝamon's Brut (The Chronicles of England, Middle English, West Midlands):
      array("An preost wes on leoden, Laȝamon was ihoten
He wes Leovenaðes sone -- liðe him be Drihten.
He wonede at Ernleȝe at æðelen are chirechen,
Uppen Sevarne staþe, sel þar him þuhte,
Onfest Radestone, þer he bock radde."),
      // From the Tagelied of Wolfram von Eschenbach (Middle High German):
      array("Sîne klâwen durh die wolken sint geslagen,
er stîget ûf mit grôzer kraft,
ich sih in grâwen tägelîch als er wil tagen,
den tac, der im geselleschaft
erwenden wil, dem werden man,
den ich mit sorgen în verliez.
ich bringe in hinnen, ob ich kan.
sîn vil manegiu tugent michz leisten hiez."),
      // Some lines of Odysseus Elytis (Greek):
      // Monotonic:
      array("Τη γλώσσα μου έδωσαν ελληνική
το σπίτι φτωχικό στις αμμουδιές του Ομήρου.
Μονάχη έγνοια η γλώσσα μου στις αμμουδιές του Ομήρου.
από το Άξιον Εστί
του Οδυσσέα Ελύτη"),
      // Polytonic:
      array("Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ
τὸ σπίτι φτωχικὸ στὶς ἀμμουδιὲς τοῦ Ὁμήρου.
Μονάχη ἔγνοια ἡ γλῶσσα μου στὶς ἀμμουδιὲς τοῦ Ὁμήρου.
ἀπὸ τὸ Ἄξιον ἐστί
τοῦ Ὀδυσσέα Ἐλύτη"),
      // The first stanza of Pushkin's Bronze Horseman (Russian):
      array("На берегу пустынных волн
Стоял он, дум великих полн,
И вдаль глядел. Пред ним широко
Река неслася; бедный чёлн
По ней стремился одиноко.
По мшистым, топким берегам
Чернели избы здесь и там,
Приют убогого чухонца;
И лес, неведомый лучам
В тумане спрятанного солнца,
Кругом шумел."),
      // Šota Rustaveli's Veṗxis Ṭq̇aosani, ̣︡Th, The Knight in the Tiger's Skin (Georgian):
      array("ვეპხის ტყაოსანი შოთა რუსთაველი
ღმერთსი შემვედრე, ნუთუ კვლა დამხსნას სოფლისა შრომასა, ცეცხლს, წყალსა და მიწასა, ჰაერთა თანა მრომასა; მომცნეს ფრთენი და აღვფრინდე, მივჰხვდე მას ჩემსა ნდომასა, დღისით და ღამით ვჰხედვიდე მზისა ელვათა კრთომაასა."),
      // Tamil poetry of Subramaniya Bharathiyar: சுப்ரமணிய பாரதியார் (1882-1921):
      array("யாமறிந்த மொழிகளிலே தமிழ்மொழி போல் இனிதாவது எங்கும் காணோம்,
பாமரராய் விலங்குகளாய், உலகனைத்தும் இகழ்ச்சிசொலப் பான்மை கெட்டு,
நாமமது தமிழரெனக் கொண்டு இங்கு வாழ்ந்திடுதல் நன்றோ? சொல்லீர்!
தேமதுரத் தமிழோசை உலகமெலாம் பரவும்வகை செய்தல் வேண்டும்."),
      // Kannada poetry by Kuvempu — ಬಾ ಇಲ್ಲಿ ಸಂಭವಿಸು
      array("ಬಾ ಇಲ್ಲಿ ಸಂಭವಿಸು ಇಂದೆನ್ನ ಹೃದಯದಲಿ
ನಿತ್ಯವೂ ಅವತರಿಪ ಸತ್ಯಾವತಾರ
ಮಣ್ಣಾಗಿ ಮರವಾಗಿ ಮಿಗವಾಗಿ ಕಗವಾಗೀ...
ಮಣ್ಣಾಗಿ ಮರವಾಗಿ ಮಿಗವಾಗಿ ಕಗವಾಗಿ
ಭವ ಭವದಿ ಭತಿಸಿಹೇ ಭವತಿ ದೂರ
ನಿತ್ಯವೂ ಅವತರಿಪ ಸತ್ಯಾವತಾರ || ಬಾ ಇಲ್ಲಿ ||"),
    );
  }

  /**
   * @dataProvider utf8Provider
   */
  public function test_replace_non_utf8($s, $expect = null) {
    if ($expect === null) {
      $expect = $s;
    }
    $result = out\replace_non_utf8($s);
    $this->assertEquals($expect, $result, 's='.urlencode($s) . "\nexpect=".urlencode($expect) . "\nresult=".urlencode($result));
  }

  public function controlProvider() {
    $R = out\REPLACEMENT_CHARACTER;

    // U+007F - U+009F : latin1 control characters (plus for convenience \x7F)
    $latin1 = $latin1_expect = '';
    for ($n = 0x7F; $n <= 0x9F; $n++) {
      $latin1 .= iconv('UCS-2', 'UTF-8', pack('n', $n));
      $latin1_expect .= $R;
    }

    // U+FDD0 - U+FDEF : internal processing code block
    // http://stackoverflow.com/questions/5188679/whats-the-purpose-of-the-noncharacters-ufdd0-to-ufdef
    $arabic = $arabic_expect = '';
    for ($n = 0xFDD0; $n <= 0xFDEF; $n++) {
      $arabic .= iconv('UCS-2', 'UTF-8', pack('n', $n));
      $arabic_expect .= $R;
    }

    // U+[0-10]FFF[EF] : byte order markers
    $bo = $bo_expect = '';
    for ($n = 0; $n <= 0x10; $n++) {
      $bo .= iconv('UCS-4', 'UTF-8', pack('nnnn', $n, 0xFFFE, $n, 0xFFFF));
      $bo_expect .= "$R$R";
    }

    return array(
      array("\x00\x01", "$R$R"),
      array("\x02 foo bar \x03", "$R foo bar $R"),
      array("\x04 foo\nbar \x05", "$R foo\nbar $R"),
      array("\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F", "$R$R$R\t\n$R\x0C\r$R$R"),
      array("\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F", "$R$R$R$R$R$R$R$R$R$R$R$R$R$R$R$R"),
      array($latin1, $latin1_expect),
      array($arabic, $arabic_expect),
      array($bo, $bo_expect),
    );
  }

  /**
   * @dataProvider controlProvider
   */
  public function test_replace_control_characters($s, $expect = null) {
    if ($expect === null) {
      $expect = $s;
    }
    $result = out\replace_control_characters($s);
    $this->assertEquals($expect, $result, 's='.urlencode($s) . "\nexpect=".urlencode($expect) . "\nresult=".urlencode($result));
  }
}
