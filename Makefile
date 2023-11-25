.PHONY: bash bash-root build ps rebuild start startd

NAME = pico
VERSION = 1.0.0
TARGETS := $(MAKEFILE_LIST)
SHELL := /bin/bash

include ./make/Makefile.composer
include ./make/Makefile.database
include ./make/Makefile.docker
include ./make/Makefile.misc
include ./make/Makefile.php-fpm
include ./make/Makefile.sylius

update:
	git pull
	make composer-update
	make restart-php

update-dev:
	git pull
	make composer-update-dev
	make restart-php

install:
	git pull
	make composer-install
	make restart-php

install-dev:
	git pull
	make composer-install-dev
	make restart-php
