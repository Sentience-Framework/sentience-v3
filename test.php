<?php

$sqlite3 = new SQLite3('sqlite/sentience.sqlite3');

$serializedConnection = serialize($sqlite3);

unset($sqlite3);

$newSqlite3 = unserialize($serializedConnection);

/** @var SQLite3 $newSqlite3 */

echo $newSqlite3->lastInsertRowID();
