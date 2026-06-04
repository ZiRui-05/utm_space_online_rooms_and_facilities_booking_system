<?php
function ensure_issue_reports_table(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS issue_reports (
            issue_id INT(11) NOT NULL AUTO_INCREMENT,
            reported_by INT(11) NOT NULL,
            issue_title VARCHAR(150) NOT NULL,
            issue_type ENUM('maintenance','safety','cleanliness','equipment','access','other') NOT NULL,
            description TEXT NOT NULL,
            related_resource_type ENUM('room','facility','none') NOT NULL DEFAULT 'none',
            room_id INT(11) DEFAULT NULL,
            facility_id INT(11) DEFAULT NULL,
            priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
            issue_status ENUM('pending','in_review','resolved','closed') NOT NULL DEFAULT 'pending',
            issue_hidden TINYINT(1) NOT NULL DEFAULT 0,
            hidden_by INT(11) DEFAULT NULL,
            hidden_at DATETIME DEFAULT NULL,
            attachment_name VARCHAR(255) DEFAULT NULL,
            attachment_mime VARCHAR(100) DEFAULT NULL,
            attachment_base64 LONGTEXT DEFAULT NULL,
            admin_remarks TEXT DEFAULT NULL,
            reviewed_by INT(11) DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (issue_id),
            KEY issue_reports_reported_by_idx (reported_by),
            KEY issue_reports_room_id_idx (room_id),
            KEY issue_reports_facility_id_idx (facility_id),
            KEY issue_reports_status_idx (issue_status),
            KEY issue_reports_hidden_idx (issue_hidden),
            KEY issue_reports_priority_idx (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $columns = [];
    $result = $conn->query('SHOW COLUMNS FROM issue_reports');
    while ($column = $result->fetch_assoc()) {
        $columns[$column['Field']] = true;
    }

    if (!isset($columns['issue_hidden'])) {
        $conn->query('ALTER TABLE issue_reports ADD COLUMN issue_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER issue_status');
    }
    if (!isset($columns['hidden_by'])) {
        $conn->query('ALTER TABLE issue_reports ADD COLUMN hidden_by INT(11) DEFAULT NULL AFTER issue_hidden');
    }
    if (!isset($columns['hidden_at'])) {
        $conn->query('ALTER TABLE issue_reports ADD COLUMN hidden_at DATETIME DEFAULT NULL AFTER hidden_by');
    }

    $indexes = [];
    $result = $conn->query('SHOW INDEX FROM issue_reports');
    while ($index = $result->fetch_assoc()) {
        $indexes[$index['Key_name']] = true;
    }
    if (!isset($indexes['issue_reports_hidden_idx'])) {
        $conn->query('ALTER TABLE issue_reports ADD KEY issue_reports_hidden_idx (issue_hidden)');
    }
}
?>
