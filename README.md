# Good Buy METRO

This is a slightly modified version of the software that was used to power an
internal discount campaign at Galeria Kaufhof GmbH.


## Setting up a local development evironment on Mac OS X

Here is the high-level overview of what needs to be done to set up a local
development environment on Mac OS X:

* Install Make
* Install wkhtmltopdf
* Install and start MySQL 5.5
* Install Bower (globally)
* Install PHP 5.6, with mysqlnd, icu4c, jpeg, libpng, apcu
* Set `date.timezone` in your php.ini
* Install Composer
* Clone this repository
* run `make install`, provide the credentials for your MySQL server
* run `make database`
* run `php app/console assets:install --symlink`

While there are many ways to achieve this setup, here is a recipe that is
known to work on a fresh install of Mac OS X Yosemite:

* Install Xcode® from the App Store
* Install Homebrew: `ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"`
* `brew install Caskroom/cask/wkhtmltopdf`
* `brew install nodejs`
* `sudo npm install -g bower`
* `brew install homebrew/php/php56-mysqlnd_ms`
* `brew install homebrew/php/php56`
* `brew install homebrew/php/php56-apcu`
* Edit `/usr/local/etc/php/5.6/php.ini`, find the `date.timezone` setting,
  remove the leading `;`, and set the value to `'Europe/Berlin'`
* `curl -sS https://getcomposer.org/installer | php`
* `sudo mv composer.phar /usr/bin/composer`
* `brew install homebrew/versions/mysql55`
* `brew link --force homebrew/versions/mysql55`
* `mysql -uroot -e 'CREATE DATABASE goodbye_metro;'`
* `git clone git@gitlab.gkh-setu.de:gkh/goodbye-metro.git`
* `cd goodbye-metro`
* `make install`
* `make database`


## Working with database entities and migrations

### Workflow

In order to ensure that entities and migrations are in sync, it is recommended
to proceed as follows:

* Create new entity via `php app/console doctrine:generate:entity`
* Afterwards, create the matching migration via
  `php app/console doctrine:migrations:diff`
* Verify that the new migrations class is correct
* Immediately apply the migration via `make migrations`

These are wrapped in `make entity` and `make migration-via-diff`.

If you change entities at a later point in time, always generate a new
migration using the diff method accordingly. When doing so, always make sure
to apply a newly created migration via `make migrations` *before* creating the
next migration via `make migration-via-diff`!

Remember you can always generate getters and setters via
`php app/console doctrine:generate:entities AppBundle/Entity/YourEntityName --no-backup`.


### Conventions

The shortcut name must include the `AppBundle` namespace, and must be named in
singular form, like this: `AppBundle:Customer`.

Field names must be `lowercase_with_underscores`.
