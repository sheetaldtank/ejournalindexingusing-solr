<?php
require __DIR__ . '/db.php';

$result = $conn->query("SELECT * FROM ejournals1");

$docs = [];

while ($row = $result->fetch_assoc()) {

    $start = (int)$row['date_first_issue_online'];
    $end   = (int)$row['date_last_issue_online'];

    $coverage_text = '';
    if (!empty($start)) {
        $coverage_text = $start;
        $coverage_text .= !empty($end) ? " to $end" : " Onwards";
    }
$docs[] = [
    "id" => "journal_" . $row['id'],
    "title" => $row['publication_title'] ?? '',
    "publisher" => $row['publisher_name'] ?? '',
    "collection" => $row['collectionname'] ?? '',
    "provider" => $row['provider_name'] ?? '',

    "main_subject" => array_values(array_filter(array_map('trim', explode(';', $row['main_subject'] ?? '')))),
    "subject_keywords" => array_values(array_filter(array_map('trim', explode(';', $row['subject_keywords'] ?? '')))),
    "supergroup" => array_values(array_filter(array_map('trim', explode(';', $row['supergroup'] ?? '')))),

    "searchable_subject" => array_values(array_filter(array_map('trim', explode(';', $row['main_subject'] ?? '')))),
    "searchable_keywords" => array_values(array_filter(array_map('trim', explode(';', $row['subject_keywords'] ?? '')))),
    "searchable_supergroup" => array_values(array_filter(array_map('trim', explode(';', $row['supergroup'] ?? '')))),

    "issn" => $row['issn'] ?? '',
    "eissn" => $row['eissn'] ?? '',
    "title_url" => $row['title_url'] ?? ''
];
}
$url = "http://127.0.0.1:8983/solr/ejournals/update?commit=true";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($docs));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;

