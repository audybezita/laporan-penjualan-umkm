<?php
require_once __DIR__ . '/functions.php';
requireLogin();

session_destroy();
redirect('index.php');
