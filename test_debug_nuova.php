<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php_errors.log");

require_once __DIR__ . "/inc/config.php";
require_once __DIR__ . "/inc/functions.php";
require_once __DIR__ . "/inc/auth.php";
require_once __DIR__ . "/inc/db.php";
require_once __DIR__ . "/inc/newsletter.php";

$_SESSION["utente_id"] = 1;
$_SESSION["utente_email"] = "asodomich@gmail.com";
$_SESSION["utente_nome"] = "Amministratore";
$_SESSION["utente_ruolo"] = "admin";
$_SESSION["admin_lang"] = "it";

$route = "newsletter_nuova";
$file = __DIR__ . "/admin/pages/" . $route . ".php";

if (is_file($file)) {
    ob_start();
    require $file;
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . "\n";
    echo "Has closing html: " . (strpos($output, "</html>") !== false ? "YES" : "NO") . "\n";
    echo "Output lines: " . count(explode("\n", $output)) . "\n";
    echo "Has form close: " . (strpos($output, "</form>") !== false ? "YES" : "NO") . "\n";
}
