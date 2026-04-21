<?php
function esc($s){
    if (is_array($s)) {
        $s = implode(', ', $s);
    }
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function build_query($extra = []){
    $params = array_merge($_GET, $extra);
    return http_build_query($params);
}
function build_in($conn, $arr) {
    return implode(",", array_map(fn($x) =>
        "'" . $conn->real_escape_string($x) . "'", $arr));
}
function build_where($conn, $exclude = null){

    $where = [];

    global $q, $alpha, $subjects, $mains, $groups, $collections, $publishers;

    if ($q) {
        $s = $conn->real_escape_string($q);
        $where[] = "(publication_title LIKE '%$s%' OR subject_keywords LIKE '%$s%')";
    }

    if ($alpha) {
        $a = $conn->real_escape_string($alpha);
        $where[] = "publication_title LIKE '$a%'";
    }

    if ($exclude !== 'subject' && $subjects)
        $where[] = "id IN (SELECT journal_id FROM ejournal_subjects WHERE subject IN (" . build_in($conn,$subjects) . "))";

    if ($exclude !== 'main' && $mains)
        $where[] = "id IN (SELECT journal_id FROM ejournal_main_subjects WHERE main_subject IN (" . build_in($conn,$mains) . "))";

    if ($exclude !== 'group' && $groups)
        $where[] = "id IN (SELECT journal_id FROM ejournal_supergroups WHERE supergroup IN (" . build_in($conn,$groups) . "))";

    if ($exclude !== 'collection' && $collections)
        $where[] = "collectionname IN (" . build_in($conn,$collections) . ")";

    if ($exclude !== 'publisher' && $publishers)
        $where[] = "publisher_name IN (" . build_in($conn,$publishers) . ")";

    return $where ? "WHERE " . implode(" AND ", $where) : "";
}
?>