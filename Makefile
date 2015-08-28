install:
	composer install
	bower install

database:
	php app/console doctrine:database:create

entity:
	php app/console doctrine:generate:entity

migration-via-diff:
	php app/console doctrine:migrations:diff

migrations:
	php app/console doctrine:migrations:migrate
