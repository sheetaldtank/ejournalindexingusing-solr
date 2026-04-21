<?php
function fetchSolrResults($q, $alpha, $subjects, $mains, $groups, $collections, $publishers, $offset, $limit) {

    $params = [];

    // main query
if (!empty($q)) {

    $params[] = "defType=edismax";
    $params[] = "q=" . urlencode($q);

    // EXACT same fields that worked in Solr test
    //$params[] = "qf=title^4 searchable_subject^3 searchable_keywords^3 searchable_supergroup^2 ";
    $params[] = "qf=title^4";
    $params[] = "qf=searchable_keywords^3";
    $params[] = "qf=issn^2";
    $params[] = "qf=eissn^2";
    $params[] = "qf=publisher^1";
    
    // REQUIRED to avoid zero results issue
    $params[] = "mm=1";

} else {
    $params[] = "q=*:*";
}

    // filters
    foreach ($subjects as $s) {
        if (!empty($s)) {
            $params[] = "fq=subject_keywords:" . urlencode("\"$s\"");
        }
    }

    foreach ($mains as $m) {
        if (!empty($m)) {
            $params[] = "fq=main_subject:" . urlencode("\"$m\"");
        }
    }

    foreach ($groups as $g) {
        if (!empty($g)) {
            $params[] = "fq=supergroup:" . urlencode("\"$g\"");
        }
    }

    foreach ($collections as $c) {
        if (!empty($c)) {
            $params[] = "fq=collection:" . urlencode("\"$c\"");
        }
    }

    foreach ($publishers as $p) {
        if (!empty($p)) {
            $params[] = "fq=publisher:" . urlencode("\"$p\"");
        }
    }

    // A-Z filter
    if (!empty($alpha)) {
        $params[] = "fq=title:" . urlencode($alpha . "*");
    }

    // pagination
    $params[] = "start=$offset";
    $params[] = "rows=$limit";
    $params[] = "wt=json";

    // facets
    $params[] = "facet=true";
    $params[] = "facet.field=collection";
    $params[] = "facet.field=supergroup";
    $params[] = "facet.field=main_subject";
    $params[] = "facet.field=subject_keywords";
    $params[] = "facet.field=publisher";

    // IMPORTANT: no sorting for now
    // $params[] = "sort=title asc";  ❌ keep disabled

    // build URL
    $url = "http://127.0.0.1:8983/solr/ejournals/select?" . implode('&', $params);

    // request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return [
        'results' => $data['response']['docs'] ?? [],
        'total' => $data['response']['numFound'] ?? 0,
        'facets' => $data['facet_counts']['facet_fields'] ?? []
    ];
}


