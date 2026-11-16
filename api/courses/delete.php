<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$id = $_GET['id'] ?? 0;
(new \Controllers\CourseController())->destroy($id);
