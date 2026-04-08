<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

require 'db_configuration.php'; // provides $db (mysqli)

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Select only the columns we actually render — avoids pulling unused data
$sql = "SELECT id, grade_level, image, title, author, publisher, publishYear, numPages, available"
     . ($isAdmin ? ", price" : "")
     . " FROM books ORDER BY id ASC";

$result = $db->query($sql);

$rows = '';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id         = intval($row['id']);
        $gradeLevel = htmlspecialchars($row['grade_level']  ?? '', ENT_QUOTES);
        $image      = htmlspecialchars($row['image']        ?? '', ENT_QUOTES);
        $title      = htmlspecialchars($row['title']        ?? '', ENT_QUOTES);
        $author     = htmlspecialchars($row['author']       ?? '', ENT_QUOTES);
        $publisher  = htmlspecialchars($row['publisher']    ?? '', ENT_QUOTES);
        $year       = htmlspecialchars($row['publishYear']  ?? '', ENT_QUOTES);
        $pages      = htmlspecialchars($row['numPages']     ?? '', ENT_QUOTES);
        $status     = $row['available'] == '0' ? 'Not Available' : 'Available';

        $rows .= "<tr>";
        $rows .= "<td>{$gradeLevel}</td>";
        $rows .= "<td><img src='{$image}' onerror=\"this.src='images/books/default.png'\" loading='lazy' style='max-height:60px;'></td>";
        $rows .= "<td>{$title}</td>";
        $rows .= "<td>{$author}</td>";
        $rows .= "<td>{$publisher}</td>";
        $rows .= "<td>{$year}</td>";
        $rows .= "<td>{$pages}</td>";

        if ($isAdmin) {
            $price  = htmlspecialchars($row['price'] ?? '', ENT_QUOTES);
            $imgVal = htmlspecialchars($row['image'] ?? '', ENT_QUOTES);
            $rows .= "<td>{$price}</td>";
            $rows .= "<td>{$status}</td>";
            $rows .= "<td style='white-space:nowrap;'>
                <a href='book_edit.php?book_id={$id}&book_image={$imgVal}'
                   style='display:inline-block;padding:5px 10px;background:#f0fad8;color:#274606;border:1.5px solid #99d930;border-radius:6px;font-weight:700;font-size:.82em;text-decoration:none;margin-bottom:4px;'>
                   ✏️ Edit
                </a>
                <form action='book_delete.php' method='POST' style='display:inline;'
                      onsubmit=\"return confirm('Delete this book? This cannot be undone.');\">
                    <input type='hidden' name='book_image' value='{$imgVal}'>
                    <input type='hidden' name='book_id'    value='{$id}'>
                    <button type='submit' name='delete_book'
                            style='padding:5px 10px;background:#fff0f0;color:#c00;border:1.5px solid #f99;border-radius:6px;font-weight:700;font-size:.82em;cursor:pointer;'>
                        🗑 Delete
                    </button>
                </form>
            </td>";
        } else {
            $rows .= "<td>{$status}</td>";
        }

        $rows .= "</tr>";
    }
    $result->free();
}

header('Content-Type: application/json');
echo json_encode(['data' => $rows]);