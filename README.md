# PHP-RBAC v3.x

PHP-RBAC is an authorization library for PHP. It provides developers with NIST Level 2 Hierarchical Role Based Access Control and more, in the fastest implementation yet.

**Current Stable Release:** [PHP-RBAC v3.0](https://github.com/nchizhov/inok-rbac)

## Connect With Us

* PHP-RBAC Base Home Page: [http://phprbac.net/](http://phprbac.net/)
* PHP-RBAC Base Documentation: [http://phprbac.net/docs_contents.php](http://phprbac.net/docs_contents.php)
* PHP-RBAC Base API: [http://phprbac.net/api.php](http://phprbac.net/api.php)
* Issue Tracker: [https://github.com/nchizhov/inok-rbac/issues?state=open](https://github.com/nchizhov/inok-rbac/issues?state=open)

## What is a Rbac System?

Take a look at the "[Before You Begin](http://phprbac.net/docs_before_you_begin.php)" section of our [Documentation](http://phprbac.net/docs_contents.php) to learn what an RBAC system is and what PHP-RBAC has to offer you and your project.

## NIST Level 2 Compliance

For information regarding NIST RBAC Levels, please see [This Paper](http://csrc.nist.gov/rbac/sandhu-ferraiolo-kuhn-00.pdf).

For more great resources see the [NIST RBAC Group Page](http://csrc.nist.gov/groups/SNS/rbac/).

## Installation

You can now use [Composer](https://getcomposer.org/) to install the PHP-RBAC code base.

For Installation Instructions please refer to the "[Getting Started](http://phprbac.net/docs_getting_started.php)" section of our [Documentation](http://phprbac.net/docs_contents.php).

## Usage ##

**Instantiating a PHP-RBAC Object**
    
With a 'use' statement:
```php
        use Inok\RBAC\Rbac;
        
        $rbac = new Rbac($db, $tablePrefix);
```
, where 
* **$db** - PDO Object (supports MySQL, SQLite)
* **$tablePrefix** - RBAC-tables prefix (default: _phprbac__)

## Tests ##

Xml-files for Unit Tests:
- phpunit.mysql.xml - For **MySQL** (**MariaDB**)
- phpunit.sqlite.xml - For **SQLite**

Fill correct data for database connection in needed phpunit xml file:
* **DB_DSN** - Data source name 
* **DB_USER** - Database username 
* **DB_PASSWD** - Database password

Run:
```sh
    vendor/bin/phpunit -c xml_file_name
```

## PHP-RBAC and PSR

PHP-RBAC's Public API is now fully PSR-4 compliant.

You can now:

* Use Composer to install/update PHP-RBAC
* Use any PSR-4 compliant autoloader with PHP-RBAC
* Use the included autoloader to load PHP-RBAC

**If you notice any conflicts with PSR compliance please [Submit an Issue](https://github.com/nchizhov/inok-rbac/issues/new).**

### How You Can Help

* Report Bugs, Enhancement Requests or Documentation errors using our [Issue Tracker](https://github.com/nchizhov/inok-rbac/issues?state=open)
* [Choose a Bug](https://github.com/nchizhov/inok-rbac/issues?state=open) to work on and submit a Pull Request
* Make helpful suggestions and contributions to the [Documentation](http://phprbac.net/docs_contents.php) using our [Issue Tracker](https://github.com/nchizhov/inok-rbac/issues?state=open)

