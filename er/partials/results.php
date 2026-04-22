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

<b>Subject:</b> <?=esc($row['main_subject'])?> |
<b>Discipline:</b> <?=esc($row['supergroup'])?>

</div>
<?php endwhile; ?>