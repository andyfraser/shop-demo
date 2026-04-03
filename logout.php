<?php
require_once __DIR__ . '/bootstrap.php';
session();
session_regenerate_id(true);
session_destroy();
redirect('index.php');
