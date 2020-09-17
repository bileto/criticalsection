# Critical Sections for PHP [![Build Status](https://travis-ci.org/bileto/CriticalSection.svg?branch=master)](https://travis-ci.org/bileto/CriticalSection)

## Description

Lightweight class supporting critical section locking in PHP.

It requires **PHP >= 7.1** and no other dependency.

## Example

```php
$pdo = new PDO('...');
$driver = new Bileto\CriticalSection\Driver\PdoPgsqlDriver($pdo);
$criticalSection = new Bileto\CriticalSection\CriticalSection($driver);

$criticalSection->enter('Section Label');

// Perform set of steps of critical tasks

$criticalSection->leave('Section Label');
```
