-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2026 at 03:38 AM
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

--
-- Dumping data for table `abc_items`
--

INSERT INTO `abc_items` (`id`, `abc_id`, `item_number`, `unit`, `quantity`, `description`, `supplier1_price`, `supplier2_price`, `supplier3_price`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'ream', 20, 'Bond Paper (Short)', 235.00, 225.00, 178.00, '2026-06-09 06:04:43', '2026-06-09 06:04:43'),
(2, 1, 2, 'ream', 20, 'Bond Paper (Long)', 245.00, 235.00, 179.00, '2026-06-09 06:04:43', '2026-06-09 06:04:43'),
(3, 1, 3, 'box', 15, 'Ballpen (Black)', 175.00, 170.00, 150.00, '2026-06-09 06:04:43', '2026-06-09 06:04:43'),
(4, 1, 4, 'box', 15, 'Ballpen (Blue)', 175.00, 170.00, 150.00, '2026-06-09 06:04:43', '2026-06-09 06:04:43'),
(5, 1, 5, 'pcs', 20, 'Folder (Long)', 200.00, 195.00, 180.00, '2026-06-09 06:04:43', '2026-06-09 06:04:43');

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `abstracts_of_canvass`
--

INSERT INTO `abstracts_of_canvass` (`id`, `abc_no`, `rfq_id`, `date_of_advertisement`, `date_of_opening`, `supplier1_name`, `supplier2_name`, `supplier3_name`, `chairman_name`, `chairman_designation`, `vice_chairman_name`, `vice_chairman_designation`, `member1_name`, `member1_designation`, `member2_name`, `member2_designation`, `member3_name`, `member3_designation`, `approved_by_name`, `approved_by_designation`, `recommendation`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'ABC-2026-0001', 1, '2026-06-09', '2026-06-09', 'KCC Mall of Marbel', 'National Book Store', 'Kristan Educational Supply', 'MIGUEL A. PEÑALOZA', 'Chairman', 'ATTY. MAE P. GALONG', 'Vice Chairman', 'ATTY. REUBEN P. ESCARLAN', 'Member', 'ARNOLD B. AUMENTO', 'Member', 'RIZALYN C. ISNANI-CONCHA', 'Member', 'ATTY. KEYSIE M. GOMEZ', 'Regional Director', 'Kristan Educational Supply', '2026-06-09 06:04:43', '2026-06-09 06:41:17', NULL, NULL);

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bac_resolutions`
--

INSERT INTO `bac_resolutions` (`id`, `resolution_no`, `title`, `preamble`, `operative_part`, `further_resolved`, `closing`, `date`, `place`, `chairperson_name`, `chairperson_designation`, `vice_chairperson_name`, `vice_chairperson_designation`, `member1_name`, `member1_designation`, `member2_name`, `member2_designation`, `member3_name`, `member3_designation`, `approved_by_name`, `approved_by_designation`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, '01-001', 'AUTHORIZING THE CHR XII TO PROCURE FUEL, OIL, AND LUBRICANTS THROUGH CASH ADVANCE AND/OR REIMBURSEMENT', 'WHEREAS, the Government Procurement Policy Board (GPPB) through its Resolution No. 24-2019 dated 30 October 2019 resolved to approved the inclusion of Section 53.14 in the 2016 Revised IRR of RA 9184 on the Direct Retail Purchase (DRP) of Petroleum Fuel, Oil and Lubricant (POL) products and the amendments to the affected provisions of its Annex \\u201cH\\u201d entitled \\u201cConsolidated Guidelines for the Alternative Methods of Procurement;\n\nWHEREAS, under the aforementioned Section, DRP is allowed where it is found to be the best modality for the procurement of non-bulk POL products\\u2026;\n\nWHEREAS, Direct Retail Purchase falls under Negotiated Procurement of Section 53 as an alternative mode of procurement and is therefore subject to the rules and procedures stated in Annex H;\n\nWHEREAS, under Annex H, General Guidelines, item J, the BAC and the HOPE are directed to issue a resolution to delegate to specific officials, personnel, committee or office in the Procuring Entity the conduct of Direct Retail Purchase to efficiently and expeditiously deal with the pressing need sought to be addressed;', 'NOW, THEREFORE, for and in consideration of the foregoing premises, hereby resolve as it is hereby resolved to RECOMMEND to apply Section 53.14 of the 2016 revised IRR of R.A. No. 9184 as mode for the purchase of POL products;', 'FURTHER RESOLVED, to delegate and authorize the Commission on Human Rights Regional Office XII (CHR XII) to procure the fuel allocation for CHR official vehicles under Direct Retail Purchase (DRP) through cash advance and/or reimbursement.', 'SO RESOLVED.', '2026-06-09', 'Koronadal City, Philippines', 'MIGUEL A. PEÑALOZA', 'Chairperson', 'ATTY. MAE P. GALONG', 'Vice-Chairperson', 'ARNOLD B. AUMENTO', 'Member', 'RIZALYN C. ISNANI-CONCHA', 'Member', 'ATTY. REUBEN P. ESCARLAN', 'Member', 'ATTY. KEYSIE M. GOMEZ', 'Head of the Procuring Entity', '2026-06-09 08:02:07', '2026-06-09 08:02:07', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3', 'i:1;', 1781049516),
('laravel-cache-livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer', 'i:1781049516;', 1781049516),
('laravel-cache-livewire-rate-limiter:c249f2149727eeb79f1792b01e586e68c4ec6608', 'i:1;', 1781042552),
('laravel-cache-livewire-rate-limiter:c249f2149727eeb79f1792b01e586e68c4ec6608:timer', 'i:1781042552;', 1781042552);

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

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Office Supplies', 'Office Supplies category', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(2, 'Paper Products', 'Paper Products category', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(3, 'Writing Instruments', 'Writing Instruments category', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(4, 'Cleaning Materials', 'Cleaning Materials category', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(5, 'IT Equipment', 'IT Equipment category', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(6, 'Furniture', 'Furniture category', '2026-06-08 05:05:19', '2026-06-08 05:05:19');

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

--
-- Dumping data for table `iar_items`
--

INSERT INTO `iar_items` (`id`, `iar_id`, `stock_no`, `unit`, `description`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'ream', 'Bond Paper (Short)', 20, '2026-06-09 07:37:31', '2026-06-09 07:37:31'),
(2, 1, NULL, 'ream', 'Bond Paper (Long)', 20, '2026-06-09 07:37:31', '2026-06-09 07:37:31'),
(3, 1, NULL, 'box', 'Ballpen (Black)', 15, '2026-06-09 07:37:31', '2026-06-09 07:37:31'),
(4, 1, NULL, 'box', 'Ballpen (Blue)', 15, '2026-06-09 07:37:32', '2026-06-09 07:37:32'),
(5, 1, NULL, 'pcs', 'Folder (Long)', 20, '2026-06-09 07:37:32', '2026-06-09 07:37:32');

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inspection_acceptance_reports`
--

INSERT INTO `inspection_acceptance_reports` (`id`, `iar_no`, `po_id`, `supplier_name`, `po_no`, `date`, `requisitioning_office_dept`, `inspector_name`, `inspector_designation`, `inspection_date`, `inspection_complete`, `inspection_partial`, `acceptor_name`, `acceptor_designation`, `acceptance_date`, `acceptance_complete`, `acceptance_partial`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'IAR-2026-0001', 1, 'Kristan Educational Supply', 'PO-2026-0001', '2026-06-09', 'CHR-XII', 'ANA FE B. GALANTO', 'Admin. Assistant II', '2026-06-09', 1, 0, 'RHODELIA J. MANDOLADO', 'Admin. Officer IV', '2026-06-09', 1, 0, '2026-06-09 07:37:31', '2026-06-09 07:37:31', NULL, NULL);

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
(8, '2026_06_08_133746_create_requisitions_table', 2),
(9, '2026_06_08_133747_create_requisition_items_table', 2),
(10, '2026_06_08_141301_add_division_code_and_sequence_number_to_requisitions_table', 3),
(11, '2026_06_08_142602_add_stock_no_to_supplies_table', 4),
(12, '2026_06_09_000001_update_requisition_status_enum', 5),
(13, '2026_06_09_000002_add_available_stock_to_requisition_items', 6),
(14, '2026_06_09_000003_add_price_to_supplies_table', 7),
(15, '2026_06_09_000004_create_purchase_requests_table', 8),
(16, '2026_06_09_000005_create_purchase_request_items_table', 8),
(17, '2026_06_09_000006_add_supply_id_to_purchase_request_items', 9),
(18, '2026_06_09_000007_create_request_for_quotations_table', 10),
(19, '2026_06_09_000008_add_supplier_info_to_request_for_quotations', 11),
(20, '2026_06_09_000009_create_abstracts_of_canvass_table', 12),
(21, '2026_06_09_000010_create_purchase_orders_table', 13),
(22, '2026_06_09_000011_create_inspection_acceptance_reports_table', 14),
(23, '2026_06_09_000012_create_bac_resolutions_table', 15),
(24, '2026_06_09_213822_add_role_to_users_table', 16),
(25, '2026_06_09_214721_create_otps_table', 17),
(26, '2026_06_09_214730_add_phone_to_users_table', 17),
(27, '2026_06_10_000001_add_editing_lock_to_purchase_management_tables', 18);

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `po_items`
--

INSERT INTO `po_items` (`id`, `po_id`, `stock_no`, `unit`, `description`, `quantity`, `unit_cost`, `amount`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'ream', 'Bond Paper (Short)', 20, 178.00, 3560.00, '2026-06-09 07:11:59', '2026-06-09 07:11:59'),
(2, 1, NULL, 'ream', 'Bond Paper (Long)', 20, 179.00, 3580.00, '2026-06-09 07:11:59', '2026-06-09 07:11:59'),
(3, 1, NULL, 'box', 'Ballpen (Black)', 15, 150.00, 2250.00, '2026-06-09 07:11:59', '2026-06-09 07:11:59'),
(4, 1, NULL, 'box', 'Ballpen (Blue)', 15, 150.00, 2250.00, '2026-06-09 07:11:59', '2026-06-09 07:11:59'),
(5, 1, NULL, 'pcs', 'Folder (Long)', 20, 180.00, 3600.00, '2026-06-09 07:11:59', '2026-06-09 07:11:59');

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_no`, `abc_id`, `supplier_name`, `address`, `tin`, `mode_of_procurement`, `date`, `place_of_delivery`, `delivery_term`, `date_of_delivery`, `payment_term`, `total_amount`, `amount_in_words`, `conforme_name`, `conforme_date`, `authorized_official_name`, `authorized_official_designation`, `funds_available_by`, `funds_available_designation`, `alobs_no`, `alobs_amount`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'PO-2026-0001', 1, 'Kristan Educational Supply', 'Koronadal City', '98765434567898', 'Mode of Procurement', '2026-06-09', 'Carpenter Hill', 'Delivery Term', NULL, 'Cheque', 0.00, NULL, 'Kristan Educational Supply', '2026-06-09', 'ATTY. KEYSIE M. GOMEZ', 'Director', 'ANA FE B. GALANTO', 'Admin Asst. II/Budget Officer', NULL, NULL, '2026-06-09 07:11:59', '2026-06-09 07:11:59', NULL, NULL);

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`id`, `pr_no`, `date`, `office_division`, `rc_code`, `code`, `name_of_project`, `purpose`, `source_of_fund`, `approved_budget`, `requested_by_name`, `requested_by_designation`, `approved_by_name`, `approved_by_designation`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'PR-2026-0001', '2026-06-09', 'CHR XII', 'RC Code', 'Code', 'Name of Project', 'For Legal Supply', 'Source of Fund', 20.00, 'Rodel C. Linugao', 'Administrative Aide - 1', 'Atty. Keysie M. Gomez', 'Director', '2026-06-09 03:33:25', '2026-06-09 03:33:25', NULL, NULL);

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

--
-- Dumping data for table `purchase_request_items`
--

INSERT INTO `purchase_request_items` (`id`, `purchase_request_id`, `stock_property_no`, `reorder_level`, `unit`, `item_description`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `supply_id`) VALUES
(1, 1, 'STK-0001', 5, 'ream', 'Bond Paper (Short)', 20, NULL, '2026-06-09 03:33:25', '2026-06-09 03:33:25', 1),
(2, 1, 'STK-0002', 5, 'ream', 'Bond Paper (Long)', 20, NULL, '2026-06-09 03:33:25', '2026-06-09 03:33:25', 2),
(3, 1, 'STK-0003', 10, 'box', 'Ballpen (Black)', 15, NULL, '2026-06-09 03:33:25', '2026-06-09 03:33:25', 3),
(4, 1, 'STK-0004', 10, 'box', 'Ballpen (Blue)', 15, NULL, '2026-06-09 03:33:25', '2026-06-09 03:33:25', 4),
(5, 1, 'STK-0005', 20, 'pcs', 'Folder (Long)', 20, NULL, '2026-06-09 03:33:25', '2026-06-09 03:33:25', 5);

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_for_quotations`
--

INSERT INTO `request_for_quotations` (`id`, `rfq_no`, `purchase_request_id`, `date`, `company_name`, `address`, `contact_number`, `canvassed_by_name`, `canvassed_by_designation`, `quoted_by_name`, `quoted_by_supplier`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'RFQ-2026-0001', 1, '2026-06-09', NULL, NULL, NULL, 'Rodel C. Linugao', 'Administrative Aide-1', NULL, NULL, '2026-06-09 04:38:41', '2026-06-09 04:38:41', NULL, NULL);

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

--
-- Dumping data for table `requisitions`
--

INSERT INTO `requisitions` (`id`, `entity_name`, `fund_cluster`, `division`, `division_code`, `sequence_number`, `responsibility_center_code`, `office`, `ris_no`, `purpose`, `status`, `requested_by_name`, `requested_by_designation`, `requested_by_date`, `approved_by_name`, `approved_by_designation`, `approved_by_date`, `issued_by_name`, `issued_by_designation`, `issued_by_date`, `received_by_name`, `received_by_designation`, `received_by_date`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'XXX-2026-0001', 'For office use', 'received', 'Rodel C. Linugao', 'Administrative Aide - 1', '2026-06-08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 07:06:40', '2026-06-08 21:40:18'),
(2, 'Commission on Human Rights RO-12', '01 Fund Cluster', 'Administrative', 'ASD', 1, 'RC-ADM-001', 'CHR', 'ASD-2026-0001', 'for admin use', 'received', 'Rodel C. Linugao', 'Administrative Aide - 1', '2026-06-09', 'Rhodelia J. Mandolado', 'Admin. Officer IV', '2026-06-09', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 23:27:55', '2026-06-08 23:34:03'),
(3, 'Commission on Human Rights RO-12', '01 Fund Cluster', 'Administrative', 'ASD', 2, 'RC-ADM-001', 'CHR', 'ASD-2026-0002', 'For office use', 'received', 'Rodel C. Linugao', 'Administrative Aide - 1', '2026-06-09', 'Rhodelia J. Mandolado', 'Admin. Officer IV', '2026-06-09', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 01:08:40', '2026-06-09 01:11:57');

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

--
-- Dumping data for table `requisition_items`
--

INSERT INTO `requisition_items` (`id`, `requisition_id`, `stock_no`, `supply_id`, `unit`, `description`, `quantity_requested`, `stock_available`, `available_stock`, `quantity_issued`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'STK-0001', 1, 'ream', 'Bond Paper (Short)', 5, NULL, NULL, 0, NULL, '2026-06-08 07:06:40', '2026-06-08 07:06:40'),
(2, 1, 'STK-0002', 2, 'ream', 'Bond Paper (Long)', 2, NULL, NULL, 0, NULL, '2026-06-08 07:06:40', '2026-06-08 07:06:40'),
(3, 1, 'STK-0007', 7, 'pcs', 'Stapler', 4, NULL, NULL, 0, NULL, '2026-06-08 07:06:40', '2026-06-08 07:06:40'),
(4, 1, 'STK-0012', 12, 'pcs', 'Ink Cartridge', 1, NULL, NULL, 0, NULL, '2026-06-08 07:06:40', '2026-06-08 07:06:40'),
(5, 1, 'STK-0005', 5, 'pcs', 'Folder (Long)', 10, NULL, NULL, 0, NULL, '2026-06-08 07:06:40', '2026-06-08 07:06:40'),
(6, 2, 'STK-0011', 11, 'box', 'Paper Clips', 1, NULL, 24, 0, 'for admin', '2026-06-08 23:27:55', '2026-06-08 23:27:55'),
(7, 2, 'STK-0006', 6, 'pcs', 'Folder (Short)', 1, NULL, 160, 0, 'for admin', '2026-06-08 23:27:55', '2026-06-08 23:27:55'),
(8, 2, 'STK-0015', 15, 'pcs', 'Trash Bin', 1, NULL, 36, 0, 'available', '2026-06-08 23:27:55', '2026-06-08 23:27:55'),
(9, 2, 'STK-0014', 14, 'pcs', 'Keyboard', 1, NULL, 12, 0, NULL, '2026-06-08 23:27:55', '2026-06-08 23:27:55'),
(10, 2, 'STK-0008', 8, 'box', 'Staples', 1, NULL, 30, 0, NULL, '2026-06-08 23:27:55', '2026-06-08 23:27:55'),
(11, 3, 'STK-0011', 11, 'box', 'Paper Clips', 1, NULL, 24, 0, NULL, '2026-06-09 01:08:40', '2026-06-09 01:08:40');

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

--
-- Dumping data for table `rfq_items`
--

INSERT INTO `rfq_items` (`id`, `rfq_id`, `stock_property_no`, `unit`, `item_description`, `quantity`, `unit_cost`, `created_at`, `updated_at`) VALUES
(1, 1, 'STK-0001', 'ream', 'Bond Paper (Short)', 20, NULL, '2026-06-09 04:38:41', '2026-06-09 04:38:41'),
(2, 1, 'STK-0002', 'ream', 'Bond Paper (Long)', 20, NULL, '2026-06-09 04:38:41', '2026-06-09 04:38:41'),
(3, 1, 'STK-0003', 'box', 'Ballpen (Black)', 15, NULL, '2026-06-09 04:38:41', '2026-06-09 04:38:41'),
(4, 1, 'STK-0004', 'box', 'Ballpen (Blue)', 15, NULL, '2026-06-09 04:38:41', '2026-06-09 04:38:41'),
(5, 1, 'STK-0005', 'pcs', 'Folder (Long)', 20, NULL, '2026-06-09 04:38:41', '2026-06-09 04:38:41');

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
('3gZGjHy4EdhvsQ3pAN5mQ2GQllNEzhtoohVU6ATU', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRzJiWUwxdXpaOTdJS1ByaGRpSW1HZmpRWWU0SlYwOHVnV2x5N1RCVyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7czo1OiJyb3V0ZSI7czozMDoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiODZlNTFhNDBlOGY3MTBhZTM0ZGIyOGE2ZGYxNTg2MjE2NTFmZTJiZmQ0MWQ5NWM4YTk3NjRlZGI4MjQ2NzA1ZiI7fQ==', 1781055429);

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

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `supply_id`, `type`, `quantity`, `unit_price`, `reference_number`, `notes`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'in', 100, 387.00, 'PO-2026-0001', 'Initial stock', '2026-05-26', '2026-06-08 05:05:19', '2026-06-08 22:24:14'),
(2, 2, 'in', 30, 112.00, 'PO-2026-0002', 'Initial stock', '2026-06-05', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(3, 3, 'in', 25, 319.00, 'PO-2026-0003', 'Initial stock', '2026-05-30', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(4, 4, 'in', 20, 487.00, 'PO-2026-0004', 'Initial stock', '2026-05-22', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(5, 5, 'in', 100, 437.00, 'PO-2026-0005', 'Initial stock', '2026-06-03', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(6, 6, 'in', 80, 280.00, 'PO-2026-0006', 'Initial stock', '2026-05-31', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(7, 7, 'in', 8, 36.00, 'PO-2026-0007', 'Initial stock', '2026-05-24', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(8, 8, 'in', 15, 300.00, 'PO-2026-0008', 'Initial stock', '2026-05-19', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(9, 9, 'in', 40, 343.00, 'PO-2026-0009', 'Initial stock', '2026-05-16', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(10, 10, 'in', 5, 38.00, 'PO-2026-0010', 'Initial stock', '2026-05-23', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(11, 11, 'in', 12, 162.00, 'PO-2026-0011', 'Initial stock', '2026-05-25', '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(12, 12, 'in', 4, 229.00, 'PO-2026-0012', 'Initial stock', '2026-06-02', '2026-06-08 05:05:20', '2026-06-08 05:05:20'),
(13, 13, 'in', 3, 55.00, 'PO-2026-0013', 'Initial stock', '2026-05-15', '2026-06-08 05:05:20', '2026-06-08 05:05:20'),
(14, 14, 'in', 6, 447.00, 'PO-2026-0014', 'Initial stock', '2026-05-28', '2026-06-08 05:05:20', '2026-06-08 05:05:20'),
(15, 15, 'in', 18, 200.00, 'PO-2026-0015', 'Initial stock', '2026-05-24', '2026-06-08 05:05:20', '2026-06-08 05:05:20'),
(16, 16, 'in', 7, 368.00, 'PO-2026-0016', 'Initial stock', '2026-05-21', '2026-06-08 05:05:20', '2026-06-08 05:05:20'),
(17, 2, 'out', 5, 112.00, 'PO-2O26-0002', NULL, '2026-06-08', '2026-06-08 05:25:00', '2026-06-08 05:27:23'),
(18, 2, 'in', 45, 245.00, 'PO-2O26-0002', 'For office use', '2026-06-09', '2026-06-08 22:29:02', '2026-06-08 22:29:02');

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

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'National Book Store', 'Juan Dela Cruz', 'nbs@email.com', '123-4567', NULL, '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(2, 'Office Warehouse', 'Maria Santos', 'ow@email.com', '234-5678', NULL, '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(3, 'Paper Direct', 'Pedro Reyes', 'pd@email.com', '345-6789', NULL, '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(4, 'Tech Supplies Inc.', 'Ana Gonzales', 'tsi@email.com', '456-7890', NULL, '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(5, 'CleanCo Supplies', 'Jose Rizal', 'cleanco@email.com', '567-8901', NULL, '2026-06-08 05:05:19', '2026-06-08 05:05:19');

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

--
-- Dumping data for table `supplies`
--

INSERT INTO `supplies` (`id`, `stock_no`, `category_id`, `supplier_id`, `name`, `description`, `unit`, `reorder_level`, `current_stock`, `price`, `created_at`, `updated_at`) VALUES
(1, 'STK-0001', 1, 1, 'Bond Paper (Short)', NULL, 'ream', 5, 150, NULL, '2026-06-08 05:05:19', '2026-06-08 22:24:14'),
(2, 'STK-0002', 1, 1, 'Bond Paper (Long)', NULL, 'ream', 5, 100, NULL, '2026-06-08 05:05:19', '2026-06-08 22:29:02'),
(3, 'STK-0003', 3, 1, 'Ballpen (Black)', NULL, 'box', 10, 50, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(4, 'STK-0004', 3, 1, 'Ballpen (Blue)', NULL, 'box', 10, 40, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(5, 'STK-0005', 2, 2, 'Folder (Long)', NULL, 'pcs', 20, 200, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(6, 'STK-0006', 2, 2, 'Folder (Short)', NULL, 'pcs', 20, 160, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(7, 'STK-0007', 3, 1, 'Stapler', NULL, 'pcs', 5, 16, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(8, 'STK-0008', 3, 1, 'Staples', NULL, 'box', 10, 30, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(9, 'STK-0009', 2, 2, 'Clear Book', NULL, 'pcs', 15, 80, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(10, 'STK-0010', 3, 1, 'Whiteboard Marker', NULL, 'box', 10, 10, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(11, 'STK-0011', 3, 1, 'Paper Clips', NULL, 'box', 20, 24, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(12, 'STK-0012', 5, 4, 'Ink Cartridge', NULL, 'pcs', 3, 8, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(13, 'STK-0013', 5, 4, 'Mouse', NULL, 'pcs', 5, 6, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(14, 'STK-0014', 5, 4, 'Keyboard', NULL, 'pcs', 5, 12, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(15, 'STK-0015', 4, 5, 'Trash Bin', NULL, 'pcs', 10, 36, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34'),
(16, 'STK-0016', 4, 5, 'Broom', NULL, 'pcs', 5, 14, NULL, '2026-06-08 05:05:19', '2026-06-08 06:27:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `role`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@admin.com', NULL, 'admin', NULL, '$2y$12$uh350ThcpavgLQPAWHg7S.L1r28HJE4ebhqmHqgPUCFjYItQXwYNi', NULL, '2026-06-08 05:05:19', '2026-06-08 05:05:19'),
(2, 'Rodel Linugao', 'rodellinugao@gmail.com', '09396378986', 'staff', NULL, '$2y$12$uuD7m35ExqOrGq.YZ0kGH.SsYTMWCb10BdDsxkB853r9TpVVlpz/u', NULL, '2026-06-09 14:01:33', '2026-06-09 14:23:46');

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
  ADD KEY `po_items_po_id_foreign` (`po_id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `abstracts_of_canvass`
--
ALTER TABLE `abstracts_of_canvass`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bac_resolutions`
--
ALTER TABLE `bac_resolutions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iar_items`
--
ALTER TABLE `iar_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inspection_acceptance_reports`
--
ALTER TABLE `inspection_acceptance_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `request_for_quotations`
--
ALTER TABLE `request_for_quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rfq_items`
--
ALTER TABLE `rfq_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  ADD CONSTRAINT `po_items_po_id_foreign` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

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
