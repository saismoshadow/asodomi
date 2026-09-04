<?php
/**
 * ASODOMI – Configurazione del sito (MODELLO)
 * ------------------------------------------
 * COPIA questo file in "inc/config.php" e inserisci i tuoi dati reali:
 *
 *     cp config.example.php inc/config.php
 *
 * ⚠️ "inc/config.php" NON viene caricato su GitHub (in .gitignore):
 *    contiene credenziali del database e dati di contatto personali.
 *
 * Editá i valori di questo archivo para adaptar el sitio a tu asociación.
 */

define('SITE_NAME', 'ASODOMI');
define('SITE_FULL_NAME', 'Asociación de Dominicanos en Suiza');

// Idioma por defecto (it | es | de | fr)
define('DEFAULT_LANG', 'it');

// Idiomas disponibles (el primero es el principal)
$GLOBALS['ASODOMI_LANGS'] = ['it', 'es', 'de', 'fr'];

// Páginas públicas disponibles
$GLOBALS['ASODOMI_PAGES'] = ['inicio', 'servicios', 'eventos', 'ayuda', 'cita', 'contacto', 'blog', 'iscrizione', 'privacy', 'area-soci'];

// ── Datos de contacto (REEMPLAZAR con los datos reales) ──────────────
define('CONTACT_EMAIL', 'info@asodomi.com');        // Email donde llegan las solicitudes
define('CONTACT_PHONE', '+41 779 643 401');
define('CONTACT_WHATSAPP', '+41779643401');         // Solo números, sin espacios
define('CONTACT_ADDRESS', 'Via Scazziga 8, 6600 Muralto, Svizzera');
define('FACEBOOK_URL', 'https://www.facebook.com/asodomich/');
define('INSTAGRAM_URL', 'https://instagram.com/asodomich');

// Asunto de los emails que envían los formularios
define('MAIL_SUBJECT_AYUDA', '[ASODOMI] Nueva solicitud de ayuda');
define('MAIL_SUBJECT_CITA',  '[ASODOMI] Nueva reserva de cita');
define('MAIL_SUBJECT_SOCIO', '[ASODOMI] Nuova iscrizione socio');

// ── Base de datos MySQL (REEMPLAZAR con los datos del hosting) ──────
// IMPORTANTE: usa una password FORTE, uguale a quella del create user.
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');   // 3306 sulla maggior parte degli hosting
define('DB_NAME', 'asodomi');
define('DB_USER', 'asodomi');
define('DB_PASS', 'CAMBIAMI');
define('DB_CHARSET', 'utf8mb4');
