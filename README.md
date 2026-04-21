# 📚 E-Journals Discovery System using Solr + PHP

## 🔷 Overview

This project is a **library discovery system** built using:

- **Apache Solr 9.x** (search engine)
- **PHP + MySQL** (data source & UI)

It enables users to:
- Search across journal metadata (title, subjects, keywords, ISSN, etc.)
- Apply faceted filters (collection, subject, publisher)
- Access journal links directly

---

## 🚀 Features

- 🔍 Full-text search across multiple fields  
- 🧠 Weighted relevance ranking  
- 🏷️ Faceted filtering (collection, subject, publisher, supergroup)  
- 🔢 ISSN / EISSN search support  
- 🧩 Multi-valued field handling  
- 🔗 Clickable journal links  
- 🧼 Clean PHP-based UI  

---

## 🏗️ Architecture

```

MySQL (ejournals table)
↓
PHP (indexing.php)
↓
Apache Solr (indexed data)
↓
PHP (fetch_results.php)
↓
PHP (search.php UI)
↓
User

````

---

## 🗄️ Database Structure

Main table: `ejournals`

Key fields:

- `publication_title`
- `issn`, `eissn`
- `publisher_name`
- `collectionname`
- `main_subject`
- `subject_keywords`
- `supergroup`
- `title_url`

---

## 🔍 Solr Schema Design

### 🔹 Search Fields (`text_general`)

- `title`
- `searchable_subject`
- `searchable_keywords`
- `searchable_supergroup`
- `searchable_collection`

---

### 🔹 Facet Fields (`string`)

- `collection`
- `publisher`
- `main_subject`
- `supergroup`

---

### 🔹 Identifier Fields

- `issn`
- `eissn`

---

## ⚙️ Setup Instructions

### 1. Install and Start Solr

```bash
bin/solr start
bin/solr create -c ejournals
````

---

### 2. Configure Schema

Add fields via Solr Admin UI:

| Field                 | Type         | MultiValued |
| --------------------- | ------------ | ----------- |
| searchable_subject    | text_general | true        |
| searchable_keywords   | text_general | true        |
| searchable_supergroup | text_general | true        |
| searchable_collection | text_general | false       |

---

### 3. Index Data

Run:

```
http://localhost/er/indexing.php
```

---

### 4. Run Search Interface

```
http://localhost/er/search.php
```

---

## 🔄 Indexing Logic

Multi-valued fields are processed as:

```php
array_values(array_filter(array_map('trim', explode(';', $row['field']))))
```

---

## 🔎 Search Query Configuration

File: `fetch_results.php`

```php
$params[] = "defType=edismax";
$params[] = "q=" . urlencode($q);

$params[] = "qf=title^4 searchable_keywords^3 searchable_subject^3 searchable_supergroup^2 searchable_collection^2 issn^5 eissn^5 publisher";

$params[] = "mm=1";
```

---

## ⚠️ Important Design Principles

### 1. Separate Search vs Facet Fields

| Purpose | Field              |
| ------- | ------------------ |
| Search  | searchable_subject |
| Facet   | main_subject       |

---

### 2. Use Single `qf` Parameter

❌ Incorrect:

```php
$params[] = "qf=title";
$params[] = "qf=keywords";
```

✔ Correct:

```php
$params[] = "qf=title keywords";
```

---

### 3. Field Type Matters

| Type         | Behavior         |
| ------------ | ---------------- |
| string       | exact match only |
| text_general | full-text search |

---

## 🐞 Common Issues & Fixes

### ❌ No results returned

* Missing `mm=1`
* Incorrect `qf` configuration

---

### ❌ Atomic update error

```
Unknown operation for the an atomic update: query
```

✔ Remove `"query"` from indexing payload

---

### ❌ Field not searchable

* Ensure field type = `text_general`
* Reindex data

---

## 📂 Project Structure

```
/er
 ├── search.php
 ├── fetch_results.php
 ├── indexing.php
 ├── db.php
 ├── css/
 └── js/
```

---

## 🔮 Future Enhancements

* 🔠 Autocomplete (typeahead search)
* 📊 Result highlighting
* 🔄 Sorting options (A–Z / relevance)
* 🧠 Spellcheck (“Did you mean”)
* 📈 Usage analytics

---

## 👤 Author

Developed as part of a **library discovery system initiative**.

---

## 📜 License

Open for academic and institutional use.

