USE INFORMATION_SCHEMA;

SELECT DISTINCT
    TABLES.TABLE_SCHEMA AS 'DATABASE',
    TABLES.TABLE_NAME AS 'TABLE',
    COLUMNS.COLUMN_NAME AS 'COLUMN',
    COLUMNS.COLUMN_TYPE AS 'COLUMN TYPE',
    COLUMNS.IS_NULLABLE AS 'COLUMN NULLABLE',
    COLUMNS.COLUMN_DEFAULT AS 'COLUMN DEFAULT VALUE',
    COLUMNS.EXTRA AS 'COLUMN EXTRA INFORMATION',
    KEY_COLUMN_USAGE.CONSTRAINT_NAME AS 'BOUND CONSTRAINT',
    KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME AS 'REFERENCED TABLE',
    KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME AS 'REFERENCED COLUMN'
FROM TABLES
LEFT JOIN COLUMNS ON 
    COLUMNS.TABLE_SCHEMA = TABLES.TABLE_SCHEMA AND
    COLUMNS.TABLE_NAME = TABLES.TABLE_NAME
LEFT JOIN KEY_COLUMN_USAGE ON
    KEY_COLUMN_USAGE.TABLE_SCHEMA = TABLES.TABLE_SCHEMA AND 
    KEY_COLUMN_USAGE.TABLE_NAME = TABLES.TABLE_NAME AND
    KEY_COLUMN_USAGE.COLUMN_NAME = COLUMNS.COLUMN_NAME
WHERE TABLES.TABLE_SCHEMA = 'database1'
ORDER BY TABLES.TABLE_SCHEMA ASC, TABLES.TABLE_NAME ASC, COLUMNS.COLUMN_NAME ASC;