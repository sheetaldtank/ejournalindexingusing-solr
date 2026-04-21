<form method="GET">

<h3>Collection</h3>
<?php while($r = $f_collection->fetch_assoc()): ?>
<label><input type="checkbox" name="collection[]" value="<?=esc($r['collectionname'])?>"> <?=esc($r['collectionname'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<h3>Discipline</h3>
<?php while($r = $f_group->fetch_assoc()): ?>
<label><input type="checkbox" name="group[]" value="<?=esc($r['supergroup'])?>"> <?=esc($r['supergroup'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<h3>Subject</h3>
<?php while($r = $f_main->fetch_assoc()): ?>
<label><input type="checkbox" name="main[]" value="<?=esc($r['main_subject'])?>"> <?=esc($r['main_subject'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<h3>Keywords</h3>
<?php while($r = $f_subject->fetch_assoc()): ?>
<label><input type="checkbox" name="subject[]" value="<?=esc($r['subject'])?>"> <?=esc($r['subject'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<h3>Publisher</h3>
<?php while($r = $f_publisher->fetch_assoc()): ?>
<label><input type="checkbox" name="publisher[]" value="<?=esc($r['publisher_name'])?>"> <?=esc($r['publisher_name'])?> (<?=$r['c']?>)</label><br>
<?php endwhile; ?>

<br>
<button>Apply Filters</button>

</form>