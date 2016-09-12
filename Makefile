all: clean test coverage

clean:
	rm -rf build/artifacts/*

test:
	phpunit --testsuite=translation $(TEST)

coverage:
	phpunit --testsuite=translation --coverage-html=build/artifacts/coverage $(TEST)

coverage-show:
	open build/artifacts/coverage/index.html
