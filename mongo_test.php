<?php
require 'vendor/autoload.php'; // อย่าลืมติดตั้ง mongodb library ด้วย Composer

$client = new MongoDB\Client("mongodb+srv://aun:aun1234@cluster0.gwoclsd.mongodb.net");

$db = $client->testdb;  // ชื่อฐานข้อมูล
$collection = $db->testcollection;  // ชื่อ collection

$result = $collection->find();

foreach ($result as $document) {
    var_dump($document);
}
