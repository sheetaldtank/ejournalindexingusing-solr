<?php
$f_collection = $conn->query("SELECT collectionname, COUNT(*) c FROM ejournals1 GROUP BY collectionname ORDER BY collectionname");
$f_group = $conn->query("SELECT supergroup, COUNT(*) c FROM ejournal_supergroups GROUP BY supergroup ORDER BY c DESC");
$f_main = $conn->query("SELECT main_subject, COUNT(*) c FROM ejournal_main_subjects GROUP BY main_subject ORDER BY c DESC");
$f_subject = $conn->query("SELECT subject, COUNT(*) c FROM ejournal_subjects GROUP BY subject ORDER BY c DESC LIMIT 15");
$f_publisher = $conn->query("SELECT publisher_name, COUNT(*) c FROM ejournals1 GROUP BY publisher_name ORDER BY c DESC LIMIT 15");
?>