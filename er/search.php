<?php
require  'fetch_results.php';

function esc($s){
    if (is_array($s)) {
        $s = implode(', ', $s);
    }
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function build_query($extra = []){
    return http_build_query(array_merge($_GET, $extra));
}
function highlight($text,$q){
    if(!$q) return esc($text);
    return preg_replace("/(" . preg_quote($q,'/') . ")/i","<mark>$1</mark>",esc($text));
}

/* INPUT */
$q = $_GET['q'] ?? '';
$alpha = $_GET['alpha'] ?? '';

$subjects = $_GET['subject'] ?? [];
$mains = $_GET['main'] ?? [];
$groups = $_GET['group'] ?? [];
$collections = $_GET['collection'] ?? [];
$publishers = $_GET['publisher'] ?? [];

$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page-1)*$limit;

/* FETCH */
$data = fetchSolrResults($q,$alpha,$subjects,$mains,$groups,$collections,$publishers,$offset,$limit);

$results = $data['results'];
$total = $data['total'];
$facets = $data['facets'];

$total_pages = ceil($total/$limit);
?>

<!DOCTYPE html>
<html>
<head>
<title>eJournals Discovery</title>

<style>
body{margin:0;font-family:Segoe UI;background:#f4f6f9;}
.header{background:#1f3c5b;color:#fff;padding:12px;}
.header input{padding:6px;width:300px;}
.container{display:flex;height:100vh;}
.sidebar{width:280px;background:#fff;padding:15px;border-right:1px solid #ddd;overflow:auto;}
.content{flex:1;padding:20px;overflow:auto;}

.card{
    background:#fff;
    padding:15px;
    margin-bottom:12px;
    border-radius:8px;
    box-shadow:0 2px 5px rgba(0,0,0,0.08);
}

.card h3{margin:0 0 5px;}
.card a{text-decoration:none;color:#1f3c5b;}
.card a:hover{text-decoration:underline;}

.meta{font-size:13px;color:#555;line-height:1.5;}

.facet h3{cursor:pointer;font-size:15px;margin:10px 0;}
.facet-box{max-height:150px;overflow:auto;border:1px solid #ddd;padding:5px;}

.az{text-align:center;margin:10px;}
.az a{margin:2px;padding:4px 6px;background:#ddd;border-radius:3px;text-decoration:none;}

.pagination{text-align:center;margin-top:20px;}
.pagination a{
    padding:6px 10px;
    margin:3px;
    background:#ddd;
    text-decoration:none;
    border-radius:4px;
}
.pagination .active{background:#1f3c5b;color:#fff;}

mark{background:#ffe58a;}
.search-info {
    background:#eef3f8;
    padding:8px;
    border-radius:6px;
    margin:10px 0;
}
.filters {
    margin:10px 0;
}

.chip {
    display:inline-block;
    background:#e0e7ff;
    color:#1f3c5b;
    padding:6px 10px;
    margin:4px;
    border-radius:20px;
    text-decoration:none;
    font-size:13px;
}

.chip:hover {
    background:#c7d2fe;
}
</style>
</head>

<body>

<div class="header">
<form method="GET">
<input type="text" name="q" value="<?=esc($q)?>" placeholder="Search journals...">
<button>Search</button>
<a href="search.php" style="color:white;margin-left:10px;">Reset</a>
</form>
</div>

<div class="container">

<!-- SIDEBAR -->
<div class="sidebar">

<?php
function solrFacet($name,$data,$label){
    echo "<form method='GET'>";

    foreach($_GET as $k=>$v){
        if($k!=$name){
            if(is_array($v)){
                foreach($v as $vv){
                    echo "<input type='hidden' name='{$k}[]' value='".esc($vv)."'>";
                }
            } else {
                echo "<input type='hidden' name='$k' value='".esc($v)."'>";
            }
        }
    }

    echo "<div class='facet'>";
    echo "<h3 onclick='toggleFacet(this)'>$label</h3>";
    echo "<div class='facet-box'>";

    for($i=0;$i<count($data);$i+=2){
        $val = $data[$i];
        $count = $data[$i+1];
        $checked = in_array($val,$_GET[$name] ?? []) ? "checked" : "";

        echo "<label>
        <input type='checkbox' name='{$name}[]' value='$val' $checked>
        $val <span style='color:#888'>($count)</span>
        </label><br>";
    }

    echo "</div><button>Apply</button></div></form>";
}

solrFacet('collection',$facets['collection'] ?? [],'Collection');
solrFacet('group',$facets['supergroup'] ?? [],'Discipline');
solrFacet('main',$facets['main_subject'] ?? [],'Subject');
solrFacet('subject',$facets['subject_keywords'] ?? [],'Keywords');
solrFacet('publisher',$facets['publisher'] ?? [],'Publisher');
?>

</div>

<!-- CONTENT -->
<div class="content">
<!-- RESULTS DETAILS-->
<h3><?= $total ?> results found</h3>

<?php if(!empty($q)): ?>
    <div style="margin:10px 0; font-size:14px;">
        Showing results for: 
        <b>"<?= esc($q) ?>"</b>
    </div>
<?php endif; ?>

<?php if(!empty($subjects) || !empty($mains) || !empty($groups) || !empty($collections) || !empty($publishers)): ?>
<div class="search-info">
    Filters applied:
<?php if(!empty($subjects) || !empty($mains) || !empty($groups) || !empty($collections) || !empty($publishers)): ?>
<div class="filters">

<?php
function removeFilter($key, $value){
    $params = $_GET;

    if(isset($params[$key])){
        if(is_array($params[$key])){
            $params[$key] = array_values(array_diff($params[$key], [$value]));
            if(empty($params[$key])) unset($params[$key]);
        } else {
            unset($params[$key]);
        }
    }

    return '?' . http_build_query($params);
}
?>

<?php foreach($subjects as $s): ?>
    <a class="chip" href="<?= removeFilter('subject', $s) ?>">
        Keyword: <?= esc($s) ?> ✕
    </a>
<?php endforeach; ?>

<?php foreach($mains as $m): ?>
    <a class="chip" href="<?= removeFilter('main', $m) ?>">
        Subject: <?= esc($m) ?> ✕
    </a>
<?php endforeach; ?>

<?php foreach($groups as $g): ?>
    <a class="chip" href="<?= removeFilter('group', $g) ?>">
        Discipline: <?= esc($g) ?> ✕
    </a>
<?php endforeach; ?>

<?php foreach($collections as $c): ?>
    <a class="chip" href="<?= removeFilter('collection', $c) ?>">
        Collection: <?= esc($c) ?> ✕
    </a>
<?php endforeach; ?>

<?php foreach($publishers as $p): ?>
    <a class="chip" href="<?= removeFilter('publisher', $p) ?>">
        Publisher: <?= esc($p) ?> ✕
    </a>
<?php endforeach; ?>

</div>
<?php endif; ?>

</div>
<?php endif; ?>

<div class="az">
<?php foreach(range('A','Z') as $l): ?>
<a href="?<?=build_query(['alpha'=>$l,'page'=>1])?>"><?=$l?></a>
<?php endforeach; ?>
</div>

<?php foreach($results as $row): ?>
<div class="card">

<h3>
<a href="<?= esc(is_array($row['title_url'] ?? null) ? $row['title_url'][0] : ($row['title_url'] ?? '#')) ?>" target="_blank">
<?= highlight($row['title'][0] ?? '',$q) ?>
</a>
</h3>

<div class="meta">

<b><?=esc($row['publisher'] ?? '')?></b><br>

ISSN: <?=esc($row['issn'] ?? '')?>
<?php if(!empty($row['eissn'])): ?>
| E-ISSN: <?=esc($row['eissn'])?>
<?php endif; ?>
<br>

<?php if(!empty($row['collection'])): ?>
Collection: <?=esc($row['collection'])?><br>
<?php endif; ?>

<?php /*
<?php if(!empty($row['main_subject'])): ?>
Subject: <?=esc(is_array($row['main_subject']) ? implode(', ', $row['main_subject']) : $row['main_subject'])?><br>
<?php endif; ?>

<?php if(!empty($row['supergroup'])): ?>
Discipline: <?=esc(is_array($row['supergroup']) ? implode(', ', $row['supergroup']) : $row['supergroup'])?><br>
<?php endif; ?>
*/ ?>

<?php if(!empty($row['subject_keywords'])): ?>
Subject Keywords: <?=esc(is_array($row['subject_keywords']) ? implode(', ', $row['subject_keywords']) : $row['subject_keywords'])?><br>
<?php endif; ?>

<?php if(!empty($row['coverage_text'])): ?>
Coverage: <?=esc($row['coverage_text'][0])?>
<?php endif; ?>

</div>

</div>
<?php endforeach; ?>

<!-- PAGINATION -->
<div class="pagination">
<?php
$start=max(1,$page-2);
$end=min($total_pages,$start+4);

for($i=$start;$i<=$end;$i++){
    $class = ($i==$page) ? "active" : "";
    echo "<a class='$class' href='?".build_query(['page'=>$i])."'>$i</a>";
}
?>
</div>

</div>

</div>

<script>
function toggleFacet(el){
    let box = el.nextElementSibling;
    box.style.display = (box.style.display === "none") ? "block" : "none";
}
</script>

</body>
</html>