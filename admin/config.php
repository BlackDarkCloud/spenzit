<?php
// Fill these from Hostinger hPanel > Databases > MySQL Databases
const DB_HOST='localhost', DB_NAME='u123456789_spenzit', DB_USER='u123456789_admin', DB_PASS='YOUR_DB_PASSWORD';
session_start();
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
