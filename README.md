Paquito
=======

Paquito is a set of tools allowing to easily build and publish packages
of softwares for various package managers and distributions.

How to build
------------

Clone the repository and run the following shell command:

````bash
git clone https://github.com/CosyVerif/paquito.git
cd paquito
./bin/create-phar.sh
````

It creates a `paquito` binary, that proposes several commands:

````bash
$ ./paquito
paquito version 0.1

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  check          Check validity of a YaML file
  generate       Generate a package
  generate-test  Generate a test package
  help           Displays help for a command
  list           Lists commands
  normalize      Normalize a YaML file
  parse          Parse a YaML file
  prune          Prune a structure
  update         Updates paquito to the latest version
  write          Write a YaML file
````

How to use it
-------------

Create a `paquito.yaml` file at the root of your project.
Look at examples in the following repostories:

* [hello-world](https://github.com/saucisson/hello-world)
* [paquito](https://github.com/CosyVerif/paquito)
* [imitator](https://github.com/etienneandre/imitator)

Generate a package using the folliginw command:

````bash
./paquito generate <path-to-your-project>
````

**Warning:** be sure to have [docker](https://www.docker.com) installed and
ready to run.
