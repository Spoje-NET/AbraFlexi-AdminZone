all: fresh build install

pretest:
	composer --ansi --no-interaction update
	php -f TestPreparation.php

#test: phpunit codeception

phpunit:
	./vendor/bin/phpunit --colors  --bootstrap tests/bootstrap.php --configuration phpunit.xml tests

fresh:
	echo fresh

install: 
	echo install
	
build:
	echo build
	

clean:
	rm -rf debian/abraflexi-adminzone 
	rm -rf debian/*.substvars debian/*.log debian/*.debhelper debian/files debian/debhelper-build-stamp
	rm -rf vendor/* composer.lock


deb:
	debuild -i -us -uc -b

.PHONY : install
	