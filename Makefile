install:
	composer install
	bower install

database:
	php app/console doctrine:database:create

migration:
	php app/console doctrine:migration:generate
