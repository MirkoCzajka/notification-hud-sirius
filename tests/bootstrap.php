<?php
// Fuerza entorno testing ANTES del bootstrap de Laravel
putenv("APP_ENV=testing");
$_ENV["APP_ENV"]    = "testing";
$_SERVER["APP_ENV"] = "testing";

// --- SQLite en memoria (recomendado para no tocar tu MySQL) ---
putenv("DB_CONNECTION=sqlite");
putenv("DB_DATABASE=:memory:");
$_ENV["DB_CONNECTION"] = "sqlite";
$_ENV["DB_DATABASE"]   = ":memory:";

// Autoload de Composer
require __DIR__."/../vendor/autoload.php";
