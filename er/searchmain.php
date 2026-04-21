<?php
include "db.php";

/* ---------- HELPERS ---------- */
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function build_query($extra = []){
    return http_build_query(array_merge($_GET, $extra));
}

function build_in($conn,$arr){
    return implode(",", array_map(fn($x)=>"'".$conn->real_escape_string($x)."'", $arr));
}

function highlight($text,$q){
    if(!$q) return esc($text);
    return preg_replace("/(" . preg_quote($q,'/') . ")/i","<mark>$1</mark>",esc($text));
}

/* ---------- INPUT ---------- */
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

/* ---------- WHERE BUILDER ---------- */
function build_where($conn,$exclude=null){
    global $q,$alpha,$subjects,$mains,$groups,$collections,$publishers;

    $where=[];

    if($q){
        $s=$conn->real_escape_string($q);
        $where[]="(publication_title LIKE '%$s%' OR subject_keywords LIKE '%$s%')";
    }

    if($alpha){
        $a=$conn->real_escape_string($alpha);
        $where[]="publication_title LIKE '$a%'";
    }

    if($exclude!='subject' && $subjects)
        $where[]="id IN (SELECT journal_id FROM ejournal_subjects WHERE subject IN (".build_in($conn,$subjects)."))";

    if($exclude!='main' && $mains)
        $where[]="id IN (SELECT journal_id FROM ejournal_main_subjects WHERE main_subject IN (".build_in($conn,$mains)."))";

    if($exclude!='group' && $groups)
        $where[]="id IN (SELECT journal_id FROM ejournal_supergroups WHERE supergroup IN (".build_in($conn,$groups)."))";

    if($exclude!='collection' && $collections)
        $where[]="collectionname IN (".build_in($conn,$collections).")";

    if($exclude!='publisher' && $publishers)
        $where[]="publisher_name IN (".build_in($conn,$publishers).")";

    return $where ? "WHERE ".implode(" AND ",$where) : "";
}

$where_sql = build_where($conn);

/* ---------- COUNT ---------- */
$total = $conn->query("SELECT COUNT(*) t FROM ejournals1 $where_sql")->fetch_assoc()['t'];
$total_pages = ceil($total/$limit);

/* ---------- RESULTS ---------- */
$result = $conn->query("
SELECT * FROM ejournals1
$where_sql
ORDER BY publication_title
LIMIT $limit OFFSET $offset
");

/* ---------- FACETS ---------- */
$f_collection = $conn->query("
SELECT collectionname, COUNT(*) c FROM ejournals1
".build_where($conn,'collection')."
GROUP BY collectionname ORDER BY c DESC
");

$f_group = $conn->query("
SELECT supergroup, COUNT(*) c FROM ejournal_supergroups
WHERE journal_id IN (SELECT id FROM ejournals1 ".build_where($conn,'group').")
GROUP BY supergroup ORDER BY c DESC
");

$f_main = $conn->query("
SELECT main_subject, COUNT(*) c FROM ejournal_main_subjects
WHERE journal_id IN (SELECT id FROM ejournals1 ".build_where($conn,'main').")
GROUP BY main_subject ORDER BY c DESC
");

$f_subject = $conn->query("
SELECT subject, COUNT(*) c FROM ejournal_subjects
WHERE journal_id IN (SELECT id FROM ejournals1 ".build_where($conn,'subject').")
GROUP BY subject ORDER BY c DESC
");

$f_publisher = $conn->query("
SELECT publisher_name, COUNT(*) c FROM ejournals1
".build_where($conn,'publisher')."
GROUP BY publisher_name ORDER BY c DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Discovery</title>

<style>
body{margin:0;font-family:Segoe UI;background:#f5f7fa;}
.header{background:#1f3c5b;color:white;padding:12px;}
.container{display:flex;height:100vh;}
.sidebar{width:280px;background:#fff;padding:15px;border-right:1px solid #ccc;overflow:auto;}
.content{flex:1;padding:20px;overflow:auto;}

.card{background:white;padding:15px;margin-bottom:12px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.facet-box{max-height:150px;overflow:auto;border:1px solid #ddd;padding:5px;margin-bottom:10px;}

.az{text-align:center;margin:15px;}
.pagination a{padding:6px 10px;background:#ddd;margin:2px;text-decoration:none;border-radius:4px;}
mark{background:#ffe58a;}
.facet h3{
    cursor:pointer;
    font-size:15px;
    margin-bottom:5px;
}

.facet-box{
    max-height:150px;
    overflow:auto;
}

span{
    font-size:13px;
}
</style>
</head>

<body>

<div class="header">
<form method="GET">
<input type="text" name="q" value="<?=esc($q)?>">
<button>Search</button>
<a href="search.php" style="color:white;margin-left:10px;">Reset</a>
</form>
</div>

<div class="container">

<!-- SIDEBAR -->
<div class="sidebar">

<?php
function facet($name,$data,$label,$field){
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

    while($r=$data->fetch_assoc()){
        $val = esc($r[$field]);
       $checked = in_array($val, $_GET[$name] ?? []) ? "checked" : "";

       echo "<label>
        <input type='checkbox' name='{$name}[]' value='$val' $checked>
        $val <span style='color:#888'>(".$r['c'].")</span>
        </label><br>";
       
    }

    echo "</div><button>Apply</button>";
    echo "</div> </form>";
}

facet('collection',$f_collection,'Collection','collectionname');
facet('group',$f_group,'Discipline','supergroup');
facet('main',$f_main,'Subject','main_subject');
facet('subject',$f_subject,'Keywords','subject');
facet('publisher',$f_publisher,'Publisher','publisher_name');
?>

</div>

<!-- CONTENT -->
<div class="content">

<h3><?= $total ?> results</h3>
<div style="margin-bottom:10px;">

<?php
foreach(['collection','group','main','subject','publisher'] as $f){

    if(!empty($_GET[$f])){

        foreach($_GET[$f] as $v){

            $new = $_GET[$f];
            $new = array_diff($new, [$v]);

            echo "<span style='background:#e3eaf3;padding:5px 8px;margin:3px;border-radius:4px;display:inline-block'>
                    ".esc($v)."
                    <a href='?".build_query([$f=>$new])."' style='margin-left:5px;text-decoration:none'>×</a>
                  </span>";
        }
    }
}
?>
</div>
<div class="az">
<?php foreach(range('A','Z') as $l): ?>
<a href="?<?=build_query(['alpha'=>$l,'page'=>1])?>"><?=$l?></a>
<?php endforeach; ?>
</div>

<?php while($row=$result->fetch_assoc()): ?>
<div class="card">

<h3>
<a href="<?=esc($row['title_url'])?>" target="_blank" style="text-decoration:none; color:#1f3c5b;">
<?=highlight($row['publication_title'],$q)?>
</a>
</h3>

<div style="font-size:13px; color:#555; margin-top:5px;">

<b><?=esc($row['publisher_name'])?></b><br>

ISSN: <?=esc($row['issn'])?> 
<?php if(!empty($row['eissn'])): ?>
| E-ISSN: <?=esc($row['eissn'])?>
<?php endif; ?>

<br>

<?php if(!empty($row['collectionname'])): ?>
Collection: <?=esc($row['collectionname'])?><br>
<?php endif; ?>

<?php if(!empty($row['main_subject'])): ?>
Subject: <?=esc($row['main_subject'])?><br>
<?php endif; ?>

<?php if(!empty($row['supergroup'])): ?>
Discipline: <?=esc($row['supergroup'])?><br>
<?php endif; ?>

<?php if (!empty($row['date_first_issue_online'])): ?>
    Coverage: <?= esc($row['date_first_issue_online']) ?>
    <?= !empty($row['date_last_issue_online']) 
        ? ' to ' . esc($row['date_last_issue_online']) 
        : ' Onwards'; ?>
<?php endif; ?>
</div>


</div>
<?php endwhile; ?>

<div class="pagination">
<?php
$start=max(1,$page-2);
$end=min($total_pages,$start+4);

for($i=$start;$i<=$end;$i++){
    echo "<a href='?".build_query(['page'=>$i])."'>$i</a>";
}

if($end<$total_pages){
    echo "<a href='?".build_query(['page'=>$page+1])."'>Next</a>";
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