<?php
namespace Test;
use PDO;

$pdo = new PDO("sqlsrv:server=(local);Database=test", "", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "exec getUser :Id, :LoginName";
$stmt = $pdo->prepare($sql);

$id = null;
$loginName = "user_3";
$stmt->bindParam(":Id", $id, PDO::PARAM_INT);
$stmt->bindParam(":LoginName", $loginName, PDO::PARAM_STR);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

var_dump($result);
?>