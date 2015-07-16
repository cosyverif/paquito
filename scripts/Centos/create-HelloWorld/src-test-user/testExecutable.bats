#!/usr/bin/env bats



@test "Test User" {

chaine="Hello World";
exec=`/usr/bin/hello-world`;
#echo "$exec";

[ "$chaine" = "$exec" ]

}
