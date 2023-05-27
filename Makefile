.PHONY: coverage

help: ## Shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

cs: ## Run php-cs-fixer
	vendor/bin/php-cs-fixer fix -v --config=.php-cs-fixer.dist.php

phpstan: ## run phpstan
	vendor/bin/phpstan -vvv

phpunit: ## run phpunit
	vendor/bin/phpunit

tests: ## run tests
	phpunit

coverage: ## generate coverage
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage

