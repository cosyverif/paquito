<?php
require 'vendor/autoload.php' ;
use Symfony\Component\Yaml\Yaml ;

require 'check.php' ;
require 'lop.php' ;
require 'normalize.php' ;
require 'parse.php' ;
require 'write.php' ;

$parsed = parse("paquito.yml") ;
print_r($parsed);

check($parsed) ;
$normalized=normalize($parsed);
print_r($normalized) ;

$enew=lop($normalized) ;
print_r($enew);

write($enew, "final.yml") ;
