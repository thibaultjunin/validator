# Validator

![Continuous integration](https://github.com/lefuturiste/validator/workflows/Continuous%20integration/badge.svg)

Simple php validator for PSR7 request

## How to use ?

### From PSR-7

```php
$validator = new Validator($request->getParsedBody());
```

### From php input 

```php
$validator = new Validator($_POST);
```

### Validate

```php
$validator->required('example');
$validator->notEmpty('example');
```

And more validate methods...

### If valid

```php
$validator->isValid(); // TRUE|FALSE
```

### Get errors 

(array)

```php
$validator->getErrors();
```

You can get errors in a different format, with the rules:

```php
$validator->getErrors(true);
```

### I18n

English, french and spanish are supported

```php
ValidationLanguage::setLang('fr'); // or `en` or `es`
```

## Tests

All the tests are in the `tests` folder. You can run tests with this command (do a composer install before).

on linux/mac: `vendor/bin/phpunit tests`
on windows: `vendor/bin/phpunit.bat tests`
