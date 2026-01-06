# UPGRADE.md

## PHP Version Upgrade – Compatibility Guide

This document describes the required changes to make the project run
**cleanly on PHP 8.2 or newer**, without warnings or deprecated notices.

The project was originally developed and tested on PHP 7.4–8.0,
where the language was more permissive in certain areas.

---

## Supported PHP Versions

| PHP Version | Status |
|------------|--------|
| 7.4        | ✔️ Fully supported |
| 8.0–8.1    | ✔️ Supported |
| 8.2+       | ⚠️ Minor changes required |

---

## 1. Removal of Dynamic Properties (Required for PHP 8.2+)

Starting with PHP 8.2, **dynamic properties are deprecated**.

### Problem
Properties are accessed that are not declared in the class:
```php
$attr->value;
$attr->originalValue;
$attr->orderByIndex;
