SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `content` text DEFAULT NULL,
  `announcement_date` date DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'published',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `announcements` (`id`, `title`, `content`, `announcement_date`, `status`, `created_at`) VALUES
(1, 'Barangay Assembly Meeting', 'The next barangay assembly meeting will be on September 28, 2025, 8:00 AM.', '2025-09-22', 'published', '2026-09-28 21:50:50'),
(2, 'Updated Service Hours', 'The barangay hall will be open from 8:00 AM to 5:00 PM, Monday to Friday.', '2025-09-18', 'published', '2026-09-28 21:50:50'),
(3, 'Community Clean-Up Drive', 'Join us for the monthly clean-up drive on September 20, 2025.', '2025-09-12', 'published', '2026-09-28 21:50:50');

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `certificate_type` varchar(120) DEFAULT NULL,
  `certificate_number` varchar(40) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `released_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `reference_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','In Process','Approved','For Payment','Completed','Rejected') DEFAULT 'Pending',
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `requests` (`id`, `reference_number`, `user_id`, `service_id`, `purpose`, `notes`, `status`, `payment_status`, `submitted_at`, `updated_at`) VALUES
(1, 'BRGY-2025-0009', 2, 4, 'New sari-sari store', NULL, 'Completed', 'Paid', '2025-09-10 09:00:00', '2026-09-28 21:50:50'),
(2, 'BRGY-2025-0010', 2, 3, 'Scholarship application', NULL, 'For Payment', 'Unpaid', '2025-09-15 09:00:00', '2026-09-28 21:50:50'),
(3, 'BRGY-2025-0011', 2, 2, 'School enrollment', NULL, 'Approved', 'Unpaid', '2025-09-18 09:00:00', '2026-09-28 21:50:50'),
(4, 'BRGY-2025-0012', 2, 1, 'Employment', NULL, 'In Process', 'Unpaid', '2025-09-20 09:00:00', '2026-09-28 21:50:50'),
(5, 'BRGY-2026-6ABBD1', 2, 1, 'gufu', NULL, 'Pending', 'Unpaid', '2026-09-28 22:41:10', '2026-09-28 22:41:10'),
(6, 'BRGY-2026-FB7B51', 3, 1, 'asfgasfagf', NULL, 'Pending', 'Unpaid', '2026-09-28 23:12:47', '2026-09-28 23:12:47'),
(7, 'BRGY-2026-44465E', 5, 2, 'fadff', NULL, 'Pending', 'Unpaid', '2026-09-28 23:31:16', '2026-09-28 23:31:16');

CREATE TABLE `request_documents` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(120) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Services',
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `processing_days` int(11) DEFAULT 1,
  `fee` decimal(10,2) DEFAULT 0.00,
  `icon` varchar(30) DEFAULT 'file',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `services` (`id`, `service_name`, `category`, `description`, `requirements`, `processing_days`, `fee`, `icon`, `status`, `created_at`) VALUES
(1, 'Barangay Clearance', 'Certificates & Clearances', 'For employment, business, travel, and other purposes.', 'Valid ID, proof of residency', 1, 50.00, 'file', 'active', '2026-09-28 21:50:50'),
(2, 'Certificate of Residency', 'Certificates & Clearances', 'Proof of residency within the barangay.', 'Valid ID', 1, 30.00, 'user', 'active', '2026-09-28 21:50:50'),
(3, 'Indigency Certificate', 'Services', 'For financial assistance and other purposes.', 'Valid ID', 1, 0.00, 'file', 'active', '2026-09-28 21:50:50'),
(4, 'Business Permit', 'Services', 'For small and new businesses.', 'DTI registration, valid ID', 3, 200.00, 'grid', 'active', '2026-09-28 21:50:50'),
(5, 'Other Services', 'Services', 'Brgy. assistance, referrals, community programs, and more.', 'Varies', 2, 0.00, 'users', 'active', '2026-09-28 21:50:50'),
(6, 'Business Permit Assistance', 'Services', 'Assistance for residents and business owners processing barangay business-related requirements.', 'Valid ID, business documents', 2, 100.00, 'file', 'active', '2026-09-29 01:30:19'),
(7, 'Community Assistance', 'Services', 'Request assistance and support for community-related concerns and needs.', 'Valid ID, supporting documents if applicable', 1, 0.00, 'file', 'active', '2026-09-29 01:30:19'),
(8, 'Barangay Complaint', 'Services', 'Submit a formal complaint or report regarding a barangay concern.', 'Valid ID, supporting evidence or documents', 3, 0.00, 'file', 'active', '2026-09-29 01:30:19'),
(9, 'Incident Report', 'Services', 'Submit an incident report for an event or concern within the barangay.', 'Valid ID, incident details', 2, 0.00, 'file', 'active', '2026-09-29 01:30:19'),
(10, 'Certificate of Indigency', 'Certificates & Clearances', 'Certificate issued to qualified residents for various assistance and legal purposes.', 'Valid ID', 1, 0.00, 'file', 'active', '2026-09-29 01:30:19'),
(11, 'Certificate of Residency', 'Certificates & Clearances', 'Certificate proving that the resident is currently residing within the barangay.', 'Valid ID', 1, 50.00, 'file', 'active', '2026-09-29 01:30:19');

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('resident','admin') NOT NULL DEFAULT 'resident',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`) VALUES
(1, 'Barangay Admin', 'admin@barangayhub.test', 'admin123', '09170000000', 'Barangay Hall', 'admin', 'active', '2026-09-28 21:50:50'),
(2, 'Juan Dela Cruz', 'juan@barangayhub.test', '$2y$10$59VOPxVZSfJwlU9HGIqzFu5Wf.I/SaSfmhLxj88mdfgNH3/aWfoE2', '09171234567', '123 Rizal St., Barangay Centro', 'resident', 'active', '2026-09-28 21:50:50'),
(3, 'Josh Chua', 'ruyujik@gmail.com', '$2y$10$v70tDRAG/EqyPlT7RCyYy.FS/nYyFrOL6SesikWdhyscY.s7kna02', NULL, NULL, '', 'active', '2026-09-28 22:56:55'),
(4, 'Kenyu', 'kencantdash@gmail.com', '$2y$10$IMRyqVA82VX024r8pJh5WuJbT6njzJQN47ocbcBandROBoae38MsG', NULL, NULL, '', 'active', '2026-09-28 23:28:32'),
(5, 'kenyu', 'ken@gmail.com', '$2y$10$iq278XjIWsC75IlI8g7X9Op2EnVViebwdbs8K3Eu04xaItVcAJd9K', NULL, NULL, '', 'active', '2026-09-28 23:30:50'),
(6, 'admin', 'john@gmail.com', '$2y$10$toWrDuVUQDaefWbYHJJQL.a2o0VE5k.7HatDN2K3XB6U8o12.VGJC', NULL, NULL, 'admin', 'active', '2026-09-28 23:57:05');

ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `service_id` (`service_id`);

ALTER TABLE `request_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `request_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

ALTER TABLE `request_documents`
  ADD CONSTRAINT `request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;
COMMIT;
