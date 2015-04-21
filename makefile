list:
	@echo "fix"
	@echo "phpcs"
	@echo "test"
	@echo "test-report"
	@echo "health"

phpcs:
	phpcs --standard=PSR2 --extensions=php --ignore=vendor/*,tests/*,cphalcon/* --warning-severity=0 ./lib

fix:
	phpcbf --standard=PSR2 --extensions=php --ignore=vendor/*,tests/*,cphalcon/* --warning-severity=0 ./

test-report:
	phpunit -v --coverage-html ./tests/report/ --colors

test:
	phpunit -v --colors

health:
	pdepend --overview-pyramid=tests/report/output.svg src/