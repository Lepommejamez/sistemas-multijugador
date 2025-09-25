<?php
$db_connection = 'sqlite:..\private\users.db';
$db = new PDO($db_connection);
$sql = 'INSERT INTO users (user_name, user_password) VALUES (:user_name, :user_password)';
$query = $db->prepare($sql);
$query->bindValue(':user_name', "admin2");
$query->bindValue(':user_password', "password");