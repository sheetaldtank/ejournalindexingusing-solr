<?php
require 'db.php';

$result = $conn->query("SELECT * FROM ejournals1");

$docs = [];

while ($row = $result->fetch_assoc()) {

    $start = (int)$row['date_first_issue_online'];
    $end   = (int)$row['date_last_issue_online'];

    $coverage_text = '';
    if (!empty($start)) {
        $coverage_text = $start;
        if (!empty($end)) {
            $coverage_text .= " to " . $end;
        } else {
            $coverage_text .= " Onwards";
        }
    }

    $subjects = array_filter(array_map('trim', explode(';', $row['subjectname'] ?? '')));

    $docs[] = [
        "id" => "journal_" . $row['id'],
        "title" => $row['publication_title'],
        "publisher" => $row['publisher_name'],
        "provider" => $row['provider_name'] ?? "ONOS",
        "collection" => $row['collectionname'] ?? "Unknown",
        "subject" => $subjects,
        "supergroup" => $row['supergroup'],
        "coverage_start" => $start,
        "coverage_end" => $end,
        "coverage_text" => $coverage_text
    ];
}

// 🔴 DEBUG: show count
echo "Records fetched: " . count($docs) . "<br>";

// send to Solr
$url = "http://localhost:8983/solr/ejournals1/update?commit=true";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($docs));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

echo "<pre>";
print_r($response);
echo "</pre>";

curl_close($ch);