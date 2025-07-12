list:
	@grep '^[^#[:space:]].*:' Makefile

setup: setup-docker setup-docker-compose

setup-docker:
	sudo apt-get update
	sudo apt-get install ca-certificates curl
	sudo install -m 0755 -d /etc/apt/keyrings
	sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
	sudo chmod a+r /etc/apt/keyrings/docker.asc
	echo "deb [arch=$(shell dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(shell grep "VERSION_CODENAME" /etc/os-release 2>/dev/null | cut -f2 -d '=') stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
	sudo apt-get update
	sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
	sudo usermod -aG docker root
	docker -v
	sudo docker run hello-world

setup-docker-compose:
	sudo curl -L "https://github.com/docker/compose/releases/download/1.23.1/docker-compose-$(shell uname -s)-$(shell uname -m)" -o /usr/local/bin/docker-compose
	sudo chmod +x /usr/local/bin/docker-compose
	docker-compose --version

deploy: pull-code clone-env init-database composer-install

clone-env:
	cp application/.env.example application/.env
pull-code:
	git pull origin HEAD

composer-install:
	docker exec marker_app composer install --ignore-platform-req=ext-exif --ignore-platform-req=ext-gd --ignore-platform-req=ext-zip
	docker exec marker_app php /var/www/artisan migrate --force
	docker exec marker_app chown -R root:www-data /var/www/bootstrap/cache
	docker exec marker_app chmod -R 775 /var/www/bootstrap/cache
	docker exec marker_app chown -R root:www-data /var/www/storage
	docker exec marker_app chmod -R 775 /var/www/storage

init-database:
	docker exec marker_database mysql -e 'CREATE DATABASE IF NOT EXISTS main'