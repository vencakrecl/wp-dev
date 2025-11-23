.PHONY:

build-phar:
	composer build:phar

run-phar:
	composer run:phar

lint-fix:
	composer lint:fix

test:
	composer lint
	composer phpstan
	composer test:unit