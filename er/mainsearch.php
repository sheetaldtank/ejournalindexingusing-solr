<?php
include "db.php";
include "config.php";
include "functions.php";

/* ---------------- WHERE ---------------- */
$where = [];

if ($q !== '') {
    $s = $conn->real_escape_string($q);
    $where[] = "(publication_title LIKE '%$s%' OR subject_keywords LIKE '%$s%')";
}

if ($alpha !== '') {
    $a = $conn->real_escape_string($alpha);
    $where[] = "publication_title LIKE '$a%'";
}

if (!empty($subjects))
    $where[] = "id IN (SELECT journal_id FROM ejournal_subjects WHERE subject IN (" . build_in($conn,$subjects) . "))";

if (!empty($mains))
    $where[] = "id IN (SELECT journal_id FROM ejournal_main_subjects WHERE main_subject IN (" . build_in($conn,$mains) . "))";

if (!empty($groups))
    $where[] = "id IN (SELECT journal_id FROM ejournal_supergroups WHERE supergroup IN (" . build_in($conn,$groups) . "))";

if (!empty($collections))
    $where[] = "collectionname IN (" . build_in($conn,$collections) . ")";

if (!empty($publishers))
    $where[] = "publisher_name IN (" . build_in($conn,$publishers) . ")";

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ---------------- SORT ---------------- */
switch($sort) {
    case 'title_desc':
        $order = "publication_title DESC";
        break;
    case 'relevance':
        $order = ($q !== '') ? "publication_title LIKE '%$q%' DESC" : "publication_title";
        break;
    default:
        $order = "publication_title ASC";
}

/* ---------------- DATA ---------------- */
$total = $conn->query("SELECT COUNT(*) t FROM ejournals1 $where_sql")->fetch_assoc()['t'];

$result = $conn->query("
SELECT * FROM ejournals1
$where_sql
ORDER BY $order
LIMIT $limit OFFSET $offset
");

/* ---------------- FACETS (DYNAMIC) ---------------- */
$base = $where_sql ?: '';

$f_collection = $conn->query("SELECT collectionname, COUNT(*) c FROM ejournals1 $base GROUP BY collectionname ORDER BY c DESC LIMIT 5");
$f_group = $conn->query("SELECT supergroup, COUNT(*) c FROM ejournal_supergroups WHERE journal_id IN (SELECT id FROM ejournals1 $base) GROUP BY supergroup LIMIT 5");
$f_main = $conn->query("SELECT main_subject, COUNT(*) c FROM ejournal_main_subjects WHERE journal_id IN (SELECT id FROM ejournals1 $base) GROUP BY main_subject LIMIT 5");
$f_subject = $conn->query("SELECT subject, COUNT(*) c FROM ejournal_subjects WHERE journal_id IN (SELECT id FROM ejournals1 $base) GROUP BY subject LIMIT 5");
$f_publisher = $conn->query("SELECT publisher_name, COUNT(*) c FROM ejournals1 $base GROUP BY publisher_name LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
<title>Discovery</title>

<style>
body{margin:0;font-family:Arial;background:#f4f6f8;}
.topbar{background:#eee;padding:10px;}
.wrapper{display:flex;}
.sidebar{width:280px;background:#fff;padding:15px;border-right:2px solid #ccc;height:100vh;overflow:auto;}
.content{flex:1;padding:20px;}
.card{background:#fff;padding:15px;margin-bottom:10px;border-radius:6px;}
.facet-box{max-height:150px;overflow:auto;border:1px solid #ddd;padding:5px;margin-bottom:10px;}
.tags span{background:#e3f2fd;padding:4px;margin:3px;display:inline-block;}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">

<form method="GET" style="display:inline;">
<input type="text" name="q" placeholder="Search..." value="<?=esc($q)?>">
<button>Search</button>
</form>

<select name="sort" onchange="this.form.submit()" form="mainForm">
<option value="title_asc" <?=($sort=='title_asc'?'selected':'')?>>A–Z</option>
<option value="title_desc" <?=($sort=='title_desc'?'selected':'')?>>Z–A</option>
<option value="relevance" <?=($sort=='relevance'?'selected':'')?>>Relevance</option>
</select>

<br><br>

<?php foreach(range('A','Z') as $l): ?>
<a href="?alpha=<?=$l?>"><?=$l?></a>
<?php endforeach; ?>

<a href="search.php" style="margin-left:10px;">Reset</a>

</div>

<div class="wrapper">

<!-- SIDEBAR -->
<div class="sidebar">
<form method="GET" id="mainForm">

<h3>Collection</h3>
<div class="facet-box">
<?php while($r=$f_collection->fetch_assoc()): ?>
<label><input type="checkbox" name="collection[]" value="<?=esc($r['collectionname'])?>"> <?=esc($r['collectionname'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>
</div>

<h3>Discipline</h3>
<div class="facet-box">
<?php while($r=$f_group->fetch_assoc()): ?>
<label><input type="checkbox" name="group[]" value="<?=esc($r['supergroup'])?>"> <?=esc($r['supergroup'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>
</div>

<h3>Subject</h3>
<div class="facet-box">
<?php while($r=$f_main->fetch_assoc()): ?>
<label><input type="checkbox" name="main[]" value="<?=esc($r['main_subject'])?>"> <?=esc($r['main_subject'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>
</div>

<h3>Keywords</h3>
<div class="facet-box">
<?php while($r=$f_subject->fetch_assoc()): ?>
<label><input type="checkbox" name="subject[]" value="<?=esc($r['subject'])?>"> <?=esc($r['subject'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>
</div>

<h3>Publisher</h3>
<div class="facet-box">
<?php while($r=$f_publisher->fetch_assoc()): ?>
<label><input type="checkbox" name="publisher[]" value="<?=esc($r['publisher_name'])?>"> <?=esc($r['publisher_name'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>
</div>

<button>Apply Filters</button>
<a href="search.php">Reset</a>

</form>
</div>

<!-- RESULTS -->
<div class="content">

<h3><?= $total ?> results</h3>

<?php while($row=$result->fetch_assoc()): ?>
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

<b>Subject:</b> <?=esc($row['main_subject'])?> |
<b>Discipline:</b> <?=esc($row['supergroup'])?>

</div>
<?php endwhile; ?>

</div>

</div>

</body>
</html>