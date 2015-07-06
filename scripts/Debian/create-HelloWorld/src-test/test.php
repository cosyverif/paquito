<?php

$var=substr(sprintf('%o', fileperms('/usr/bin/hello-world')), -4);;
if($var=='0755') {
	echo "true \n";
}
else {
	echo $var."\n";
}



?>
