<?php
include "db.php";

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/* -----------------------------
   INPUTS
------------------------------*/
$q = $_GET['q'] ?? '';
$subjects = $_GET['subject'] ?? [];
$mains = $_GET['main'] ?? [];
$groups = $_GET['group'] ?? [];
$collections = $_GET['collection'] ?? [];
$publishers = $_GET['publisher'] ?? [];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

/* -----------------------------
   WHERE
------------------------------*/
$where = [];

if ($q !== '') {
    $s = $conn->real_escape_string($q);
    $where[] = "(publication_title LIKE '%$s%' OR subject_keywords LIKE '%$s%')";
}

/* SUBJECT FILTER */
if (!empty($subjects)) {
    $safe = array_map(fn($x) => "'" . $conn->real_escape_string($x) . "'", $subjects);
    $where[] = "id IN (SELECT journal_id FROM ejournal_subjects WHERE subject IN (" . implode(",", $safe) . "))";
}

/* MAIN SUBJECT */
if (!empty($mains)) {
    $safe = array_map(fn($x) => "'" . $conn->real_escape_string($x) . "'", $mains);
    $where[] = "id IN (SELECT journal_id FROM ejournal_main_subjects WHERE main_subject IN (" . implode(",", $safe) . "))";
}

/* SUPERGROUP */
if (!empty($groups)) {
    $safe = array_map(fn($x) => "'" . $conn->real_escape_string($x) . "'", $groups);
    $where[] = "id IN (SELECT journal_id FROM ejournal_supergroups WHERE supergroup IN (" . implode(",", $safe) . "))";
}

/* COLLECTION */
if (!empty($collections)) {
    $safe = array_map(fn($x) => "'" . $conn->real_escape_string($x) . "'", $collections);
    $where[] = "collectionname IN (" . implode(",", $safe) . ")";
}

/* PUBLISHER */
if (!empty($publishers)) {
    $safe = array_map(fn($x) => "'" . $conn->real_escape_string($x) . "'", $publishers);
    $where[] = "publisher_name IN (" . implode(",", $safe) . ")";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* -----------------------------
   COUNT
------------------------------*/
$total = $conn->query("SELECT COUNT(*) as t FROM ejournals1 $where_sql")->fetch_assoc()['t'];

/* -----------------------------
   RESULTS
------------------------------*/
$result = $conn->query("
SELECT * FROM ejournals1
$where_sql
ORDER BY publication_title
LIMIT $limit OFFSET $offset
");

/* -----------------------------
   FACETS (STATIC COUNTS)
------------------------------*/
$f_subject = $conn->query("SELECT subject, COUNT(*) c FROM ejournal_subjects GROUP BY subject ORDER BY c DESC LIMIT 15");
$f_main = $conn->query("SELECT main_subject, COUNT(*) c FROM ejournal_main_subjects GROUP BY main_subject ORDER BY c DESC");
$f_group = $conn->query("SELECT supergroup, COUNT(*) c FROM ejournal_supergroups GROUP BY supergroup ORDER BY c DESC");
$f_collection = $conn->query("SELECT collectionname, COUNT(*) c FROM ejournals1 GROUP BY collectionname ORDER BY collectionname ");
$f_publisher = $conn->query("SELECT publisher_name, COUNT(*) c FROM ejournals1 GROUP BY publisher_name ORDER BY c DESC LIMIT 15");

?>

<!DOCTYPE html>
<html>
<head>
<title>E-Journals Discovery</title>
<style>
body { margin:0; font-family:Arial; background:#f4f6f8; }

.header { background:#2c3e50; padding:10px; }
.header input { padding:8px; width:300px; }

.wrapper { display:flex; }

.sidebar {
    width:280px;
    background:#fff;
    border-right:2px solid #ccc;
    padding:15px;
    height:100vh;
    overflow:auto;
}

.content { flex:1; padding:20px; }

h3 { margin-top:20px; border-bottom:1px solid #ddd; }

.card {
    background:#fff;
    padding:15px;
    margin-bottom:10px;
    border-radius:6px;
    box-shadow:0 1px 4px rgba(0,0,0,0.1);
}

.tags span {
    background:#e3f2fd;
    padding:4px 8px;
    margin:3px;
    display:inline-block;
    border-radius:4px;
    font-size:12px;
}
</style>
</head>

<body>

<div class="header">
<form method="GET">
<input type="text" name="q" placeholder="Search..." value="<?=esc($q)?>">
<button>Search</button>
</form>
</div>

<div class="wrapper">

<div class="sidebar">
<form method="GET">
<!-- COLLECTION -->
<h3>Collection</h3>
<?php while($r = $f_collection->fetch_assoc()): ?>
<label><input type="checkbox" name="collection[]" value="<?=esc($r['collectionname'])?>"> <?=esc($r['collectionname'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<!-- GROUP -->
<h3>Discipline</h3>
<?php while($r = $f_group->fetch_assoc()): ?>
<label><input type="checkbox" name="group[]" value="<?=esc($r['supergroup'])?>"> <?=esc($r['supergroup'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<!-- MAIN -->
<h3>Subject</h3>
<?php while($r = $f_main->fetch_assoc()): ?>
<label><input type="checkbox" name="main[]" value="<?=esc($r['main_subject'])?>"> <?=esc($r['main_subject'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<!-- SUBJECT -->
<h3>Keywords</h3>
<?php while($r = $f_subject->fetch_assoc()): ?>
<label><input type="checkbox" name="subject[]" value="<?=esc($r['subject'])?>"> <?=esc($r['subject'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<!-- PUBLISHER -->
<h3>Publisher</h3>
<?php while($r = $f_publisher->fetch_assoc()): ?>
<label><input type="checkbox" name="publisher[]" value="<?=esc($r['publisher_name'])?>"> <?=esc($r['publisher_name'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<br>
<button type="submit">Apply Filters</button>

</form>
</div>

<div class="content">

<h3><?= $total ?> results</h3>

<?php while($row = $result->fetch_assoc()): ?>
<div class="card">
<h2><a href="<?=esc($row['title_url'])?>" target="_blank"><?=esc($row['publication_title'])?></a></h2>
<b>Publisher:</b> <?=esc($row['publisher_name'])?><br>
<b>ISSN:</b> <?=esc($row['issn'])?> | <?=esc($row['eissn'])?><br>
<b>Collection:</b> <?=esc($row['collectionname'])?><br>

<div class="tags">
<?php foreach(explode(';',$row['subject_keywords']) as $t){
$t=trim($t); if($t) echo "<span>$t</span>";
} ?>
</div>

<b>Main:</b> <?=esc($row['main_subject'])?> |
<b>Group:</b> <?=esc($row['supergroup'])?>

</div>
<?php endwhile; ?>

</div>

</div>

</body>
</html>