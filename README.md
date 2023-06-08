# data_access_helper
PHP Data Access Layer Helper

This package can run queries to access data from a MySQL database.

It provides several classes that can connect to a MySQL database using MySQLi and executes SQL queries to perform several types of data access to database tables.

Currently, it can:

- Connect to a MySQL database server with a given user and password

- Execute prepared queries and get the results as an array or by result column name

- Access a database like a repository with functions to access repository records by performing operations to create, read, update, and delete repository items

- Manipulate database record column values according to the respective data type


//FURTHER DEVELOPMENT PHASE
- Catching exceptions better (try - catch)
- Datatable generation by the described attributes with foreign keys constraints.
- Caching
- Filtering/Searching items with more relations via BindingParam, FilterParam   
- ItemAttribute collection's joining ability to other ones via condition in memory. 
- Better data attribute's searching in specially for collectionitem(DT_LIST)'s attributes.
- Better function and parameters description
- Documentation