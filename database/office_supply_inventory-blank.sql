-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 02:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `office_supply_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `abc_items`
--

CREATE TABLE `abc_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `abc_id` bigint(20) UNSIGNED NOT NULL,
  `item_number` int(11) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `supplier1_price` decimal(12,2) DEFAULT NULL,
  `supplier2_price` decimal(12,2) DEFAULT NULL,
  `supplier3_price` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `abstracts_of_canvass`
--

CREATE TABLE `abstracts_of_canvass` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `abc_no` varchar(255) NOT NULL,
  `rfq_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_of_advertisement` date DEFAULT NULL,
  `date_of_opening` date DEFAULT NULL,
  `supplier1_name` varchar(255) DEFAULT NULL,
  `supplier2_name` varchar(255) DEFAULT NULL,
  `supplier3_name` varchar(255) DEFAULT NULL,
  `chairman_name` varchar(255) DEFAULT NULL,
  `chairman_designation` varchar(255) DEFAULT NULL,
  `vice_chairman_name` varchar(255) DEFAULT NULL,
  `vice_chairman_designation` varchar(255) DEFAULT NULL,
  `member1_name` varchar(255) DEFAULT NULL,
  `member1_designation` varchar(255) DEFAULT NULL,
  `member2_name` varchar(255) DEFAULT NULL,
  `member2_designation` varchar(255) DEFAULT NULL,
  `member3_name` varchar(255) DEFAULT NULL,
  `member3_designation` varchar(255) DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_by_designation` varchar(255) DEFAULT NULL,
  `recommendation` text DEFAULT 'National Book Store',
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bac_resolutions`
--

CREATE TABLE `bac_resolutions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resolution_no` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `preamble` text DEFAULT NULL,
  `operative_part` text DEFAULT NULL,
  `further_resolved` text DEFAULT NULL,
  `closing` text DEFAULT NULL,
  `date` date NOT NULL,
  `place` varchar(255) DEFAULT 'Koronadal City, Philippines',
  `chairperson_name` varchar(255) DEFAULT 'MIGUEL A. PEÑALOZA',
  `chairperson_designation` varchar(255) DEFAULT 'Chairperson',
  `vice_chairperson_name` varchar(255) DEFAULT 'ATTY. MAE P. GALONG',
  `vice_chairperson_designation` varchar(255) DEFAULT 'Vice-Chairperson',
  `member1_name` varchar(255) DEFAULT 'ARNOLD B. AUMENTO',
  `member1_designation` varchar(255) DEFAULT 'Member',
  `member2_name` varchar(255) DEFAULT 'RIZALYN C. ISNANI-CONCHA',
  `member2_designation` varchar(255) DEFAULT 'Member',
  `member3_name` varchar(255) DEFAULT 'ATTY. REUBEN P. ESCARLAN',
  `member3_designation` varchar(255) DEFAULT 'Member',
  `approved_by_name` varchar(255) DEFAULT 'ATTY. KEYSIE M. GOMEZ',
  `approved_by_designation` varchar(255) DEFAULT 'Head of the Procuring Entity',
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iar_items`
--

CREATE TABLE `iar_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `iar_id` bigint(20) UNSIGNED NOT NULL,
  `stock_no` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inspection_acceptance_reports`
--

CREATE TABLE `inspection_acceptance_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `iar_no` varchar(255) NOT NULL,
  `po_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `po_no` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `requisitioning_office_dept` varchar(255) DEFAULT NULL,
  `inspector_name` varchar(255) DEFAULT 'ANA FE B. GALANTO',
  `inspector_designation` varchar(255) DEFAULT 'Admin. Assistant II',
  `inspection_date` date DEFAULT NULL,
  `inspection_complete` tinyint(1) NOT NULL DEFAULT 1,
  `inspection_partial` tinyint(1) NOT NULL DEFAULT 0,
  `acceptor_name` varchar(255) DEFAULT 'RHODELIA J. MANDOLADO',
  `acceptor_designation` varchar(255) DEFAULT 'Admin. Officer IV',
  `acceptance_date` date DEFAULT NULL,
  `acceptance_complete` tinyint(1) NOT NULL DEFAULT 1,
  `acceptance_partial` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_06_08_125933_create_categories_table', 1),
(5, '2026_06_08_125934_create_suppliers_table', 1),
(6, '2026_06_08_125935_create_supplies_table', 1),
(7, '2026_06_08_125936_create_stock_transactions_table', 1),
(8, '2026_06_08_133746_create_requisitions_table', 1),
(9, '2026_06_08_133747_create_requisition_items_table', 1),
(10, '2026_06_08_141301_add_division_code_and_sequence_number_to_requisitions_table', 1),
(11, '2026_06_08_142602_add_stock_no_to_supplies_table', 1),
(12, '2026_06_09_000001_update_requisition_status_enum', 1),
(13, '2026_06_09_000002_add_available_stock_to_requisition_items', 1),
(14, '2026_06_09_000003_add_price_to_supplies_table', 1),
(15, '2026_06_09_000004_create_purchase_requests_table', 1),
(16, '2026_06_09_000005_create_purchase_request_items_table', 1),
(17, '2026_06_09_000006_add_supply_id_to_purchase_request_items', 1),
(18, '2026_06_09_000007_create_request_for_quotations_table', 1),
(19, '2026_06_09_000008_add_supplier_info_to_request_for_quotations', 1),
(20, '2026_06_09_000009_create_abstracts_of_canvass_table', 1),
(21, '2026_06_09_000010_create_purchase_orders_table', 1),
(22, '2026_06_09_000011_create_inspection_acceptance_reports_table', 1),
(23, '2026_06_09_000012_create_bac_resolutions_table', 1),
(24, '2026_06_09_213822_add_role_to_users_table', 1),
(25, '2026_06_09_214721_create_otps_table', 1),
(26, '2026_06_09_214730_add_phone_to_users_table', 1),
(27, '2026_06_10_000001_add_editing_lock_to_purchase_management_tables', 1);

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `token` varchar(6) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'email',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `po_items`
--

CREATE TABLE `po_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED NOT NULL,
  `stock_no` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_no` varchar(255) NOT NULL,
  `abc_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `mode_of_procurement` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `place_of_delivery` varchar(255) DEFAULT NULL,
  `delivery_term` varchar(255) DEFAULT NULL,
  `date_of_delivery` date DEFAULT NULL,
  `payment_term` varchar(255) DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `amount_in_words` varchar(255) DEFAULT NULL,
  `conforme_name` varchar(255) DEFAULT NULL,
  `conforme_date` date DEFAULT NULL,
  `authorized_official_name` varchar(255) DEFAULT 'ATTY. KEYSIE M. GOMEZ',
  `authorized_official_designation` varchar(255) DEFAULT 'Authorized Official',
  `funds_available_by` varchar(255) DEFAULT 'ANA FE B. GALANTO',
  `funds_available_designation` varchar(255) DEFAULT 'Admin Asst. II/Budget Officer',
  `alobs_no` varchar(255) DEFAULT NULL,
  `alobs_amount` decimal(14,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pr_no` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `office_division` varchar(255) DEFAULT NULL,
  `rc_code` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL COMMENT 'Project Code',
  `name_of_project` varchar(255) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `source_of_fund` varchar(255) DEFAULT NULL,
  `approved_budget` decimal(15,2) DEFAULT NULL,
  `requested_by_name` varchar(255) DEFAULT NULL,
  `requested_by_designation` varchar(255) DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_by_designation` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

CREATE TABLE `purchase_request_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `stock_property_no` varchar(255) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT NULL COMMENT 'Reorder level snapshot from supply at time of PR creation',
  `unit` varchar(255) DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(15,2) DEFAULT NULL COMMENT 'Leave empty until cost is determined',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supply_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_for_quotations`
--

CREATE TABLE `request_for_quotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rfq_no` varchar(255) NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `canvassed_by_name` varchar(255) DEFAULT NULL,
  `canvassed_by_designation` varchar(255) DEFAULT NULL,
  `quoted_by_name` varchar(255) DEFAULT NULL,
  `quoted_by_supplier` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--

CREATE TABLE `requisitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `fund_cluster` varchar(255) DEFAULT NULL,
  `division` varchar(255) DEFAULT NULL,
  `division_code` varchar(10) DEFAULT NULL,
  `sequence_number` int(10) UNSIGNED DEFAULT NULL,
  `responsibility_center_code` varchar(255) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `ris_no` varchar(255) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('requested','approved','issued','received','cancelled') NOT NULL DEFAULT 'requested',
  `requested_by_name` varchar(255) DEFAULT NULL,
  `requested_by_designation` varchar(255) DEFAULT NULL,
  `requested_by_date` date DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_by_designation` varchar(255) DEFAULT NULL,
  `approved_by_date` date DEFAULT NULL,
  `issued_by_name` varchar(255) DEFAULT NULL,
  `issued_by_designation` varchar(255) DEFAULT NULL,
  `issued_by_date` date DEFAULT NULL,
  `received_by_name` varchar(255) DEFAULT NULL,
  `received_by_designation` varchar(255) DEFAULT NULL,
  `received_by_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `requisition_id` bigint(20) UNSIGNED NOT NULL,
  `stock_no` varchar(255) DEFAULT NULL,
  `supply_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity_requested` int(11) NOT NULL DEFAULT 0,
  `stock_available` tinyint(1) DEFAULT NULL,
  `available_stock` int(11) DEFAULT NULL COMMENT 'Actual available stock at the time of requisition',
  `quantity_issued` int(11) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rfq_items`
--

CREATE TABLE `rfq_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rfq_id` bigint(20) UNSIGNED NOT NULL,
  `stock_property_no` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('e7pxrevJagIGUJQPdGFxGHJSKFQA5kQSkihRZD08', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoid29FaUt5ZGdJbTlHMDRRVnVwRlBaOGRnSWtmM2FRS213akd6bWFkaiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1781266584);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supply_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplies`
--

CREATE TABLE `supplies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stock_no` varchar(50) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `price` decimal(12,2) DEFAULT NULL COMMENT 'Unit cost / price per item',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `abc_items`
--
ALTER TABLE `abc_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `abc_items_abc_id_foreign` (`abc_id`);

--
-- Indexes for table `abstracts_of_canvass`
--
ALTER TABLE `abstracts_of_canvass`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `abstracts_of_canvass_abc_no_unique` (`abc_no`),
  ADD KEY `abstracts_of_canvass_rfq_id_foreign` (`rfq_id`),
  ADD KEY `abstracts_of_canvass_editing_by_user_id_foreign` (`editing_by_user_id`);

--
-- Indexes for table `bac_resolutions`
--
ALTER TABLE `bac_resolutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bac_resolutions_resolution_no_unique` (`resolution_no`),
  ADD KEY `bac_resolutions_editing_by_user_id_foreign` (`editing_by_user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `iar_items`
--
ALTER TABLE `iar_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iar_items_iar_id_foreign` (`iar_id`);

--
-- Indexes for table `inspection_acceptance_reports`
--
ALTER TABLE `inspection_acceptance_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inspection_acceptance_reports_iar_no_unique` (`iar_no`),
  ADD KEY `inspection_acceptance_reports_po_id_foreign` (`po_id`),
  ADD KEY `inspection_acceptance_reports_editing_by_user_id_foreign` (`editing_by_user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otps_identifier_index` (`identifier`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `po_items`
--
ALTER TABLE `po_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_items_po_id_foreign` (`po_id`),
  ADD KEY `po_items_category_id_foreign` (`category_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_no_unique` (`po_no`),
  ADD KEY `purchase_orders_abc_id_foreign` (`abc_id`),
  ADD KEY `purchase_orders_editing_by_user_id_foreign` (`editing_by_user_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_requests_pr_no_unique` (`pr_no`),
  ADD KEY `purchase_requests_editing_by_user_id_foreign` (`editing_by_user_id`);

--
-- Indexes for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_request_items_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `purchase_request_items_supply_id_foreign` (`supply_id`);

--
-- Indexes for table `request_for_quotations`
--
ALTER TABLE `request_for_quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_for_quotations_rfq_no_unique` (`rfq_no`),
  ADD KEY `request_for_quotations_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `request_for_quotations_editing_by_user_id_foreign` (`editing_by_user_id`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisition_items_requisition_id_foreign` (`requisition_id`),
  ADD KEY `requisition_items_supply_id_foreign` (`supply_id`);

--
-- Indexes for table `rfq_items`
--
ALTER TABLE `rfq_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rfq_items_rfq_id_foreign` (`rfq_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transactions_supply_id_foreign` (`supply_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplies`
--
ALTER TABLE `supplies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplies_stock_no_unique` (`stock_no`),
  ADD KEY `supplies_category_id_foreign` (`category_id`),
  ADD KEY `supplies_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `abc_items`
--
ALTER TABLE `abc_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `abstracts_of_canvass`
--
ALTER TABLE `abstracts_of_canvass`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bac_resolutions`
--
ALTER TABLE `bac_resolutions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iar_items`
--
ALTER TABLE `iar_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inspection_acceptance_reports`
--
ALTER TABLE `inspection_acceptance_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `po_items`
--
ALTER TABLE `po_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_for_quotations`
--
ALTER TABLE `request_for_quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rfq_items`
--
ALTER TABLE `rfq_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `abc_items`
--
ALTER TABLE `abc_items`
  ADD CONSTRAINT `abc_items_abc_id_foreign` FOREIGN KEY (`abc_id`) REFERENCES `abstracts_of_canvass` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `abstracts_of_canvass`
--
ALTER TABLE `abstracts_of_canvass`
  ADD CONSTRAINT `abstracts_of_canvass_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `abstracts_of_canvass_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `request_for_quotations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bac_resolutions`
--
ALTER TABLE `bac_resolutions`
  ADD CONSTRAINT `bac_resolutions_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `iar_items`
--
ALTER TABLE `iar_items`
  ADD CONSTRAINT `iar_items_iar_id_foreign` FOREIGN KEY (`iar_id`) REFERENCES `inspection_acceptance_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inspection_acceptance_reports`
--
ALTER TABLE `inspection_acceptance_reports`
  ADD CONSTRAINT `inspection_acceptance_reports_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inspection_acceptance_reports_po_id_foreign` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `po_items`
--
ALTER TABLE `po_items`
  ADD CONSTRAINT `po_items_po_id_foreign` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_abc_id_foreign` FOREIGN KEY (`abc_id`) REFERENCES `abstracts_of_canvass` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD CONSTRAINT `purchase_request_items_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_request_items_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `request_for_quotations`
--
ALTER TABLE `request_for_quotations`
  ADD CONSTRAINT `request_for_quotations_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `request_for_quotations_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD CONSTRAINT `requisition_items_requisition_id_foreign` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requisition_items_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rfq_items`
--
ALTER TABLE `rfq_items`
  ADD CONSTRAINT `rfq_items_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `request_for_quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplies`
--
ALTER TABLE `supplies`
  ADD CONSTRAINT `supplies_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplies_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
