<?php
require_once __DIR__ . '/bootstrap.php';
session();
session_destroy();
redirect('index.php');
