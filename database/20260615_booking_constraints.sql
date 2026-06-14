-- These temporary assertions fail with a duplicate-key error before any
-- persistent DDL when historical data violates the new invariants.
CREATE TEMPORARY TABLE migration_assert_no_resource_overlap (
    singleton TINYINT NOT NULL PRIMARY KEY
);

INSERT INTO migration_assert_no_resource_overlap (singleton)
SELECT 1
FROM (
    SELECT 1
    FROM bookings a
    JOIN bookings b
      ON a.booking_id < b.booking_id
     AND a.resource_type = b.resource_type
     AND COALESCE(a.room_id, a.facility_id) = COALESCE(b.room_id, b.facility_id)
     AND a.booking_start < b.booking_end
     AND a.booking_end > b.booking_start
    WHERE a.booking_status IN ('pending', 'approved')
      AND b.booking_status IN ('pending', 'approved')
    LIMIT 1
) conflicts
UNION ALL
SELECT 1
FROM (
    SELECT 1
    FROM bookings a
    JOIN bookings b
      ON a.booking_id < b.booking_id
     AND a.resource_type = b.resource_type
     AND COALESCE(a.room_id, a.facility_id) = COALESCE(b.room_id, b.facility_id)
     AND a.booking_start < b.booking_end
     AND a.booking_end > b.booking_start
    WHERE a.booking_status IN ('pending', 'approved')
      AND b.booking_status IN ('pending', 'approved')
    LIMIT 1
) conflicts;

DROP TEMPORARY TABLE migration_assert_no_resource_overlap;

CREATE TEMPORARY TABLE migration_assert_student_room_limit (
    singleton TINYINT NOT NULL PRIMARY KEY
);

INSERT INTO migration_assert_student_room_limit (singleton)
SELECT 1
FROM (
    SELECT b.user_id
    FROM bookings b
    JOIN users u ON u.user_id = b.user_id
    WHERE u.role = 'student'
      AND b.resource_type = 'room'
      AND b.booking_status IN ('pending', 'approved')
    GROUP BY b.user_id
    HAVING COUNT(*) > 1
    LIMIT 1
) conflicts
UNION ALL
SELECT 1
FROM (
    SELECT b.user_id
    FROM bookings b
    JOIN users u ON u.user_id = b.user_id
    WHERE u.role = 'student'
      AND b.resource_type = 'room'
      AND b.booking_status IN ('pending', 'approved')
    GROUP BY b.user_id
    HAVING COUNT(*) > 1
    LIMIT 1
) conflicts;

DROP TEMPORARY TABLE migration_assert_student_room_limit;

ALTER TABLE bookings
    ADD COLUMN request_fingerprint CHAR(64) NULL AFTER user_id,
    ADD UNIQUE KEY uniq_booking_active_request (user_id, request_fingerprint),
    ADD KEY booking_room_window_idx (resource_type, room_id, booking_status, booking_start, booking_end),
    ADD KEY booking_facility_window_idx (resource_type, facility_id, booking_status, booking_start, booking_end);

CREATE TABLE booking_resource_slots (
    resource_type ENUM('room','facility') NOT NULL,
    resource_id INT NOT NULL,
    slot_start DATETIME NOT NULL,
    booking_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (resource_type, resource_id, slot_start),
    KEY booking_resource_slots_booking_idx (booking_id),
    CONSTRAINT booking_resource_slots_booking_fk
        FOREIGN KEY (booking_id) REFERENCES bookings (booking_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE student_room_claims (
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY student_room_claims_booking_unique (booking_id),
    CONSTRAINT student_room_claims_user_fk
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT student_room_claims_booking_fk
        FOREIGN KEY (booking_id) REFERENCES bookings (booking_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

START TRANSACTION;

UPDATE bookings
SET request_fingerprint = SHA2(CONCAT_WS(
    '|',
    resource_type,
    COALESCE(room_id, facility_id),
    DATE_FORMAT(booking_start, '%Y-%m-%d %H:%i:%s'),
    DATE_FORMAT(booking_end, '%Y-%m-%d %H:%i:%s')
), 256)
WHERE booking_status IN ('pending', 'approved');

INSERT INTO booking_resource_slots (resource_type, resource_id, slot_start, booking_id)
SELECT
    b.resource_type,
    COALESCE(b.room_id, b.facility_id),
    DATE_ADD(b.booking_start, INTERVAL numbers.slot_number * 15 MINUTE),
    b.booking_id
FROM bookings b
JOIN (
    SELECT ones.n + tens.n * 10 AS slot_number
    FROM
        (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
    CROSS JOIN
        (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
) numbers
  ON DATE_ADD(b.booking_start, INTERVAL numbers.slot_number * 15 MINUTE) < b.booking_end
WHERE b.booking_status IN ('pending', 'approved');

INSERT INTO student_room_claims (user_id, booking_id)
SELECT b.user_id, b.booking_id
FROM bookings b
JOIN users u ON u.user_id = b.user_id
WHERE u.role = 'student'
  AND b.resource_type = 'room'
  AND b.booking_status IN ('pending', 'approved');

COMMIT;
