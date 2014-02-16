<?php
// this usually should be set in the php configuration
mb_internal_encoding('utf-8');

include('phyph.php');

// create an instance & load a pattern
$phyph = new Phyph();
$phyph->loadPattern('pattern/phyph.en.php');

// optionally you can now adjust a few params
$phyph->wordmin = 10; // words shorter than this value will be ignored

// now just print some hyphenated text
echo $phyph->hyphenate('Abbreviation - or some other long word.');
