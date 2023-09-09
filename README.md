# DataAccessLayerHelper
# DBRepository

Rasher PHP Data Access Layer Helper & DataBase Repository

This package provides several classes that can connect to many kinds of databases and executes SQL queries to perform any type of data access (MySQLi, PDO) to database tables.

Currently, it can:

- Connect to a database server via MySQLi or PDO extensions

- Execute prepared queries and get the results as an array or by result column name

- Access a database like a repository with functions to access repository records by performing operations to create, read, update, and delete repository items

- Manipulate database record column values according to the respective data type

- DT_ITEM, DT_LIST complex attribute types and searching abilities

- Transaction handling

- Caching the database query results

- Comparison operators to define filtering conditions

- Execute stored procedure + test (with PDO - MSSQL), data generation test

//LATER DEVELOPMENT PHASE
- Database table script generation by the described attributes
- Concurrent data managing
- Historical data handling with DBHistoricalRepository

- Better function and parameters description
- Documentation