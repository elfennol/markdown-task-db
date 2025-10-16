.PHONY: help
help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\n"}\
		/^[a-zA-Z_-]+:.*?##/\
		{printf "  \033[36m%-17s\033[0m %s\n", $$1, $$2}'\
		$(MAKEFILE_LIST)

.PHONY: qa-vendor-update
qa-vendor-update: ## Update vendors in the tools directory
	@echo "======== QA VENDOR UPDATE ========"
	@echo "-------- Update phpcs --------"
	composer update --working-dir=tools/phpcs
	@echo "-------- Update phpstan --------"
	composer update --working-dir=tools/phpstan
	@echo "-------- Update rector --------"
	composer update --working-dir=tools/rector

.PHONY: qa-vendor-install
qa-vendor-install: ## Install vendors in the tools directory
	@echo "======== QA VENDOR INSTALL ========"
	@echo "-------- Install phpcs --------"
	composer install --working-dir=tools/phpcs
	@echo "-------- Install phpstan --------"
	composer install --working-dir=tools/phpstan
	@echo "-------- Install rector --------"
	composer install --working-dir=tools/rector

.PHONY: qa-vendor-remove
qa-vendor-remove: ## Remove vendors in the tools directory
	rm -fr tools/phpcs/vendor
	rm -fr tools/phpstan/vendor
	rm -fr tools/rector/vendor

.PHONY: phpcs
phpcs: ## Run phpcs with dry run
	@echo "======== PHPCS ========"
	tools/phpcs/vendor/bin/php-cs-fixer fix --dry-run -v

.PHONY: phpstan
phpstan: ## Run phpstan analyse
	@echo "======== PHPSTAN ========"
	tools/phpstan/vendor/bin/phpstan analyse -v

.PHONY: phpunit
phpunit: ## Run phpunit
	@echo "======== PHPUNIT ========"
	bin/phpunit

.PHONY: phpunit-coverage
phpunit-coverage: ## Run phpunit coverage (/tmp/user_name/phpunit/coverage)
	@echo "======== PHPUNIT COVERAGE ========"
	XDEBUG_MODE=coverage bin/phpunit --coverage-html $$(dirname "$$(mktemp --dry-run)")/$$USER/phpunit/coverage

.PHONY: rector
rector: ## Run rector
	@echo "======== RECTOR ========"
	tools/rector/vendor/bin/rector process --dry-run

.PHONY: qa
qa: phpcs phpunit rector ## phpcs + phpunit + rector
