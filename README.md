# DataAccessLayerHelper
# DBRepository

Rasher PHP Data Access Layer Helper & DataBase Repository

This package provides several classes that can connect to many kinds of databases and executes SQL queries to perform any type of data access (MySQLi, PDO) to database tables.

Currently, it can:

- Connect to a database server via MySQLi or PDO extensions

- Execute prepared queries and get the results as an array or by result column name

- Access a database like a repository with functions to access repository records by performing operations to create, read, update, and delete repository items

- Manipulate database record column values according to the respective data type

- Better data attribute's searching specially for collectionitem (DT_LIST)'s attributes

//LATER DEVELOPMENT PHASE
- Filtering searching items with more relations via Param, FilterParam
- Transaction handling
- Caching
- Historical data handling with DBHistoricalRepository
- Datatable generation by the described attributes with foreign keys constraints.
- Merging of ItemAttribute collections
- Better function and parameters description
- Documentation