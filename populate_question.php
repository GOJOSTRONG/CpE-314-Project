<?php
// populate_questions.php
require_once 'config.php';

// --- List of security questions to insert ---
$questions = [
    "What was the name of your first pet?",
    "What is your mother's maiden name?",
    "What was the name of the street you grew up on?",
    "What was your favorite childhood cartoon?",
    "In what city were you born?"
];

try {
    $conn = getDBConnection();
    if (!$conn) {
        die("Database connection failed.");
    }

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO security_questions (question_text) VALUES (?)");

    echo "<h3>Populating Security Questions...</h3><ul>";
    foreach ($questions as $question) {
        // Check if the question already exists to avoid duplicates
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM security_questions WHERE question_text = ?");
        $checkStmt->execute([$question]);
        if ($checkStmt->fetchColumn() == 0) {
            if ($stmt->execute([$question])) {
                echo "<li>Successfully inserted: '" . htmlspecialchars($question) . "'</li>";
            } else {
                echo "<li style='color: red;'>Failed to insert: '" . htmlspecialchars($question) . "'</li>";
            }
        } else {
            echo "<li style='color: orange;'>Skipped (already exists): '" . htmlspecialchars($question) . "'</li>";
        }
    }
    echo "</ul><p><strong>Population complete! You can now delete this file.</strong></p>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>