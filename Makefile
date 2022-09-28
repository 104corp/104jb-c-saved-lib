#!/usr/bin/make -f

COMPOSER_PATH := $(shell which composer)
ifdef COMPOSER_PATH
	export GITHUB_ACCESS_TOKEN = $(shell composer config -g github-oauth.github.com)
endif

.PHONY: all deps \
tests phpcs phpstan phpcbf phpcbf.psr12

all: deps tests

deps:
	composer install

tests: phpcs phpstan
	vendor/bin/phpunit --testdox tests

tests.single:
	vendor/bin/phpunit --testdox --filter $(fun)

phpcs:
	vendor/bin/phpcs

phpstan:
	vendor/bin/phpstan analyse --memory-limit=-1

phpcbf:
	vendor/bin/phpcbf

phpcbf.psr12:
	vendor/bin/phpcbf --standard=check-psr12.xml $(shell git diff --cached --name-only)

coverage:
	phpdbg -qrr vendor/bin/phpunit