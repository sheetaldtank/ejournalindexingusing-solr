<?php
$q = $_POST['q'] ?? $_GET['q'] ?? '';
$alpha = $_POST['alpha'] ?? $_GET['alpha'] ?? '';

$subjects = $_POST['subject'] ?? $_GET['subject'] ?? [];
$mains = $_POST['main'] ?? $_GET['main'] ?? [];
$groups = $_POST['group'] ?? $_GET['group'] ?? [];
$collections = $_POST['collection'] ?? $_GET['collection'] ?? [];
$publishers = $_POST['publisher'] ?? $_GET['publisher'] ?? [];

$sort = $_POST['sort'] ?? $_GET['sort'] ?? 'title_asc';

$page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
?>