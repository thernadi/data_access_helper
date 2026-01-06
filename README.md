# DataAccessLayerHelper
# DBRepository

Rasher PHP Data Access Layer Helper & Database Repository

This package provides a flexible **Data Access Layer (DAL)** and **Repository pattern implementation** for PHP applications.
It is designed to work with complex object graphs, in-memory caching, and recursive attribute-based filtering.

The primary goal is to access database records as structured objects instead of raw arrays,
while supporting advanced filtering logic without querying the database repeatedly.

---

## Key Features

### Database Access
- Connects to database servers using **MySQLi** or **PDO**
- Supports multiple database engines (MySQL, MSSQL, etc.)
- Executes prepared SQL queries
- Fetches results as:
  - associative arrays
  - repository item objects
  - column-based result sets
- Transaction handling (BEGIN / COMMIT / ROLLBACK)

---

### Repository Pattern
- Repository-style access to database tables
- CRUD operations:
  - create
  - read
  - update
  - delete
- Database rows are mapped to **attribute-based objects**
- Strong separation between data access and business logic

---

### Attribute System
Each repository item is built from attributes that describe:
- name
- data type
- formatting
- default value
- visibility
- read-only state

Supported attribute data types include:

- Primitive types:  
  `DT_STRING`, `DT_INT`, `DT_BOOL`, `DT_FLOAT`, `DT_DATETIME`, etc.

- Complex types:
  - **DT_ITEM** – reference to a single related item
  - **DT_LIST** – collection of related items (always ends with `Collection`)

This allows building **deep object graphs** from relational data.

---

### Recursive Filtering (`find()`)

One of the core features of this library is the **recursive, in-memory filtering engine**.

- Filters work on cached repository items
- No additional database queries are executed
- Filtering supports:
  - nested DT_ITEM → DT_LIST → DT_ITEM chains
  - attribute paths using dot notation  
    Example:
    ```
    UserRolesCollection.UserRole.UserRoleSettingsCollection.Value
    ```

#### Supported comparison operators:
- `=`, `!=`
- `<`, `<=`, `>`, `>=`
- `LIKE`, `NOT LIKE`
- `IS NULL`, `IS NOT NULL`

#### Logical operators:
- `AND` (default)
- `OR`

Filtering is **context-aware**:
- When multiple conditions target the same collection,
  they must match **within the same collection element**
- This avoids false-positive matches across unrelated list items

---

### Caching
- Repository results can be cached in memory
- The `find()` method operates on cached objects
- Significantly improves performance for repeated filtering
- Ideal for:
  - authorization checks
  - configuration resolution
  - complex user-role-setting evaluations

---

### Stored Procedures & Testing
- Stored procedure execution (PDO, MSSQL)
- Built-in test scripts for:
  - data generation
  - stored procedure execution
  - filtering validation

---

## Minimum Requirements

- **PHP 7.4 or higher**
- Recommended: **PHP 7.4 – 8.0**
- PHP 8.2+ is supported with minor adjustments  
  (see `UPGRADE.md` for details)

Required PHP extensions:
- `mysqli` or `pdo`
- `pdo_mysql` / `pdo_sqlsrv` depending on database engine

---

## Design Goals

- Avoid repetitive database queries
- Work with structured, typed data instead of raw arrays
- Support complex domain models
- Allow expressive filtering without SQL complexity
- Keep business logic independent from database structure

---

## Project Status

### Implemented
- Repository pattern
- Attribute-based object mapping
- Recursive filtering engine
- DT_ITEM / DT_LIST support
- Transaction handling
- In-memory caching
- Comparison operators
- Stored procedure support

### Planned (Later Development Phase)
- Database table script generation from attribute definitions
- Concurrent data management
- Historical data handling (`DBHistoricalRepository`)
- Extended documentation
- Improved function and parameter descriptions

---

## Notes

This library is intentionally **not an ORM**.
It focuses on predictable behavior, explicit data structures,
and fine-grained control over filtering and data traversal.

For PHP version upgrade notes, see `UPGRADE.md`.
