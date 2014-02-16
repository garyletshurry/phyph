<?php
// this usually should be set in the php configuration
mb_internal_encoding('utf-8');

include('phyph.php');

// create an instance & load a pattern
$phyph = new Phyph();
$phyph->loadPattern('pattern/phyph.en.php');

// optionally you can also adjust a few params after the pattern was loaded
$phyph->wordmin = 10; // words shorter than this value, will be ignored

// now just output some text, which will be hyphenated
echo $phyph->hyphenate('Abbreviation - or some other long word.');