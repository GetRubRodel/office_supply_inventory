-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2026 at 05:29 AM
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
(1, 1, 1, 'Ream', 5, 'Book paper A4', 200.00, 198.00, 199.00, '2026-10-18 04:58:04', '2026-10-18 04:58:04'),
(2, 1, 2, 'Ream', 5, 'Book paper long', 199.25, 198.75, 201.00, '2026-10-18 04:58:04', '2026-10-18 04:58:04'),
(3, 1, 3, 'Ream', 5, 'Book paper short', 199.75, 200.00, 198.00, '2026-10-18 04:58:04', '2026-10-18 04:58:04');

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
  `recommendation` text DEFAULT NULL,
  `winning_supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `abstracts_of_canvass`
--

INSERT INTO `abstracts_of_canvass` (`id`, `abc_no`, `rfq_id`, `date_of_advertisement`, `date_of_opening`, `supplier1_name`, `supplier2_name`, `supplier3_name`, `chairman_name`, `chairman_designation`, `vice_chairman_name`, `vice_chairman_designation`, `member1_name`, `member1_designation`, `member2_name`, `member2_designation`, `member3_name`, `member3_designation`, `approved_by_name`, `approved_by_designation`, `recommendation`, `winning_supplier_id`, `status`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'ABC-2026-0001', 1, '2026-10-18', '2026-10-18', 'Starbright Home Depot INC.', 'Kristan Educational Supply', 'New Marbel Top Commercial', 'Miguel A. Peñaloza', 'Chairman', 'Atty. Mae P. Galong', 'Vice Chairman', 'Atty. Reuben P. Escarlan', 'Member', 'Arnold B. Aumento', 'Member', 'Rizalyn C. Isnani-Concha', 'Member', NULL, NULL, NULL, NULL, 'draft', '2026-10-18 04:58:04', '2026-10-18 04:58:04', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bac_resolutions`
--

CREATE TABLE `bac_resolutions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `abc_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abc_no` varchar(255) DEFAULT NULL,
  `iar_id` bigint(20) UNSIGNED DEFAULT NULL,
  `iar_no` varchar(255) DEFAULT NULL,
  `po_no` varchar(255) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `inspection_date` date DEFAULT NULL,
  `acceptance_date` date DEFAULT NULL,
  `acceptance_details` varchar(255) DEFAULT NULL,
  `items_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items_snapshot`)),
  `resolution_no` varchar(255) NOT NULL,
  `division` varchar(255) DEFAULT NULL,
  `budget_amount` varchar(255) DEFAULT NULL,
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
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_by_designation` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bac_resolutions`
--

INSERT INTO `bac_resolutions` (`id`, `abc_id`, `abc_no`, `iar_id`, `iar_no`, `po_no`, `supplier_name`, `inspection_date`, `acceptance_date`, `acceptance_details`, `items_snapshot`, `resolution_no`, `division`, `budget_amount`, `title`, `preamble`, `operative_part`, `further_resolved`, `closing`, `date`, `place`, `chairperson_name`, `chairperson_designation`, `vice_chairperson_name`, `vice_chairperson_designation`, `member1_name`, `member1_designation`, `member2_name`, `member2_designation`, `member3_name`, `member3_designation`, `approved_by_name`, `approved_by_designation`, `status`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 1, 'ABC-2026-0001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BAC RESO-2026-0001', 'Administrative Support Division', 'Two Thousand Nine Hundred Eighty Three and 75/100 Pesos Only.', 'A resolution authorizing the procurement of office supplies for {{DIVISION}} through small value procurement as provided in the Implementing Rules and Regulations (IRR) of Republic Act No. 12009.', 'WHEREAS, the Commission on Human Rights XII {{DIVISION}} requires various office supplies to ensure the continuous and efficient delivery of administrative services and daily operations;\n\nWHEREAS, Rule IV of the Implementing Rules and Regulations (IRR) of Republic Act No. 12009 allows for alternative modes of procurement for goods and services where the amount involved is within the prescribed threshold for the agency;\n\nWHEREAS, the estimated budget for this specific procurement is {{BUDGET}}, which is within the approved budget and allowable limits for this procurement mode;\n\nWHEREAS, there is a need to facilitate the immediate acquisition of said supplies to avoid any disruption in the division’s support functions, in compliance with applicable government accounting and auditing rules.', 'NOW, THEREFORE, on motion duly seconded and approved, the Bids and Awards Committee (BAC) hereby resolves to:\n\n1. AUTHORIZE the procurement of office supplies for the {{DIVISION}} utilizing the appropriate procurement mode as provided under the IRR of Republic Act No. 12009;\n\n2. ENSURE that the total amount to be procured shall not exceed the approved budget of {{BUDGET}} and that all purchases shall be properly documented with official receipts and necessary supporting documents;\n\n3. REQUIRE that all disbursements shall be subject to existing budgeting, accounting, and auditing rules and regulations.', NULL, 'SO RESOLVED.\n\nDone this {{RESOLVED_DATE}}, City of Koronadal, Philippines.', '2026-10-20', 'Koronadal City, Philippines', 'MIGUEL A. PEÑALOZA', 'Chairperson', 'ATTY. MAE P. GALONG', 'Vice-Chairperson', 'ARNOLD B. AUMENTO', 'Member', 'RIZALYN C. ISNANI-CONCHA', 'Member', 'ATTY. REUBEN P. ESCARLAN', 'Member', NULL, NULL, 'draft', '2026-10-18 05:09:33', '2026-10-18 05:09:33', NULL, NULL);

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
('laravel-cache-dashboard.issued_qty.all', 'i:0;', 1792466990),
('laravel-cache-dashboard.issued_qty.user:2', 'i:0;', 1792325793),
('laravel-cache-dashboard.pr_status.all.all', 'a:1:{s:8:\"approved\";i:1;}', 1792466990),
('laravel-cache-dashboard.pr_status.user:2.all', 'a:1:{s:5:\"draft\";i:1;}', 1792325794),
('laravel-cache-dashboard.procurement_counts', 'a:6:{i:0;i:1;i:1;i:1;i:2;i:1;i:3;i:1;i:4;i:1;i:5;i:1;}', 1792466960),
('laravel-cache-dashboard.supply_stats', 'a:3:{s:5:\"total\";i:3;s:9:\"available\";s:2:\"15\";s:5:\"value\";s:7:\"2983.75\";}', 1792466990),
('laravel-cache-inventory_report.filter.categories', 'O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:13:{i:1;s:25:\"Paper & Printing Supplies\";i:2;s:17:\"Writing Materials\";i:3;s:25:\"Folders & Filing Supplies\";i:4;s:16:\"Ink & Cartridges\";i:5;s:17:\"Adhesives & Tapes\";i:6;s:32:\"Cleaning and Sanitation Supplies\";i:7;s:20:\"Computer Accessories\";i:8;s:25:\"Electronics & Accessories\";i:10;s:16:\"Boards & Holders\";i:11;s:11:\"Stationary \";i:12;s:12:\"Office Tools\";i:13;s:6:\"Stamps\";i:14;s:17:\"Stapling Supplies\";}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1792385565),
('laravel-cache-inventory_report.filter.suppliers', 'O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:1:{i:1;s:26:\"Kristan Educational Supply\";}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1792385565),
('laravel-cache-inventory_report.filter.supplies', 'O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:3:{i:1;s:13:\"Book paper A4\";i:2;s:15:\"Book paper long\";i:3;s:16:\"Book paper short\";}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1792385565),
('laravel-cache-livewire-rate-limiter:056fc329aaaa757d31db450f525da23fde4d1b36', 'i:1;', 1792463294),
('laravel-cache-livewire-rate-limiter:056fc329aaaa757d31db450f525da23fde4d1b36:timer', 'i:1792463294;', 1792463294),
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:110:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:16:\"dashboard.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:16:\"dashboard.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:16:\"dashboard.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:17:\"dashboard.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"users.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:13:\"users.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:13:\"supplies.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:15:\"supplies.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:15:\"supplies.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:15:\"supplies.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:16:\"supplies.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:14:\"suppliers.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:16:\"suppliers.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:16:\"suppliers.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:16:\"suppliers.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:17:\"suppliers.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:15:\"categories.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:17:\"categories.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:17:\"categories.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:17:\"categories.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:18:\"categories.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:22:\"purchase_requests.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:24:\"purchase_requests.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:24:\"purchase_requests.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:24:\"purchase_requests.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:25:\"purchase_requests.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:27:\"request_for_quotations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:29:\"request_for_quotations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:29:\"request_for_quotations.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:29:\"request_for_quotations.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:30:\"request_for_quotations.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:25:\"abstracts_of_canvass.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:27:\"abstracts_of_canvass.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:27:\"abstracts_of_canvass.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:27:\"abstracts_of_canvass.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:28:\"abstracts_of_canvass.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:20:\"purchase_orders.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:22:\"purchase_orders.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:22:\"purchase_orders.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:22:\"purchase_orders.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:23:\"purchase_orders.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:34:\"inspection_acceptance_reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:36:\"inspection_acceptance_reports.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:36:\"inspection_acceptance_reports.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:36:\"inspection_acceptance_reports.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:37:\"inspection_acceptance_reports.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:20:\"bac_resolutions.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:22:\"bac_resolutions.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:22:\"bac_resolutions.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:22:\"bac_resolutions.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:23:\"bac_resolutions.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:17:\"requisitions.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:19:\"requisitions.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:19:\"requisitions.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:19:\"requisitions.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:20:\"requisitions.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:5;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:10:\"rpcis.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:12:\"rpcis.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:12:\"rpcis.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:12:\"rpcis.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:13:\"rpcis.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:17:\"rsmi_reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:19:\"rsmi_reports.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:19:\"rsmi_reports.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:19:\"rsmi_reports.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:20:\"rsmi_reports.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:22:\"inventory_reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:24:\"inventory_reports.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:24:\"inventory_reports.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:24:\"inventory_reports.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:25:\"inventory_reports.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:15:\"audit_logs.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:17:\"audit_logs.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:17:\"audit_logs.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:17:\"audit_logs.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:18:\"audit_logs.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:20:\"system_settings.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:22:\"system_settings.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:22:\"system_settings.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:22:\"system_settings.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:23:\"system_settings.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:13:\"stock_in.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:15:\"stock_in.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:15:\"stock_in.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:15:\"stock_in.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:16:\"stock_in.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:18:\"requisitions.issue\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:20:\"requisitions.receive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;i:4;i:6;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:23:\"purchase_requests.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:18:\"requisitions.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:28:\"request_for_quotations.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:26:\"abstracts_of_canvass.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:21:\"purchase_orders.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:35:\"inspection_acceptance_reports.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:21:\"bac_resolutions.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:11:\"rpcis.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:13:\"rpcis.preview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:38:\"inspection_acceptance_reports.stock_in\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:23:\"inventory_reports.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:18:\"rsmi_reports.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:35:\"procurement_monitoring_reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:5;i:3;i:6;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:37:\"procurement_monitoring_reports.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:37:\"procurement_monitoring_reports.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:37:\"procurement_monitoring_reports.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:38:\"procurement_monitoring_reports.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:36:\"procurement_monitoring_reports.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}}s:5:\"roles\";a:6:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"supply_officer\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:18:\"property_custodian\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:5:\"staff\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"division_chief\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:17:\"regional_director\";s:1:\"c\";s:3:\"web\";}}}', 1792529167);

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
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Paper & Printing Supplies', 'Includes all types of paper products such as bond paper, and index cards used for office operations.', 'active', '2026-08-08 08:12:33', '2026-10-16 23:16:57'),
(2, 'Writing Materials', 'Contains office writing supplies such as ballpens, pencils, markers, highlighters, correction tape, and permanent markers.', 'active', '2026-08-08 08:12:33', '2026-08-08 08:12:33'),
(3, 'Folders & Filing Supplies', 'Includes filing and document organization materials such as folders, binders, fasteners, expanding files, and document trays.', 'active', '2026-08-08 08:12:33', '2026-10-16 23:00:41'),
(4, 'Ink & Cartridges', 'Includes printer consumables such as ink cartridges, toner cartridges, printer ribbons, and maintenance kits.', 'active', '2026-08-08 08:12:33', '2026-10-16 23:05:11'),
(5, 'Adhesives & Tapes', 'Contains adhesive materials such as glue, paste, double-sided tape, masking tape, packaging tape, and adhesive stickers.', 'active', '2026-08-08 08:12:33', '2026-10-16 22:57:10'),
(6, 'Cleaning and Sanitation Supplies', 'Includes cleaning materials used for office maintenance such as alcohol, disinfectants, tissue paper, paper towels, and cleaning cloths.', 'active', '2026-08-08 08:12:33', '2026-10-16 22:59:29'),
(7, 'Computer Accessories', 'Covers computer-related accessories such as keyboards, mice, flash drives, USB cables, headphones, and webcams.', 'active', '2026-08-08 08:12:33', '2026-08-08 08:12:33'),
(8, 'Electronics & Accessories', 'Contains electrical office supplies such as extension cords, power strips, batteries, adapters, and light bulbs.', 'active', '2026-08-08 08:12:33', '2026-10-16 23:00:08'),
(10, 'Boards & Holders', 'Certificate frame, certificate holder, bulletin board', 'active', '2026-10-16 22:58:11', '2026-10-16 22:58:11'),
(11, 'Stationary ', 'Logbook, writing notebook, sticky notes', 'active', '2026-10-16 23:13:41', '2026-10-16 23:13:41'),
(12, 'Office Tools', 'Scissors, puncher, cutter', 'active', '2026-10-16 23:18:10', '2026-10-16 23:18:10'),
(13, 'Stamps', 'Stamp pad, stamp pad ink', 'active', '2026-10-16 23:18:43', '2026-10-16 23:18:43'),
(14, 'Stapling Supplies', 'Stapler, staple wire', 'active', '2026-10-16 23:19:14', '2026-10-16 23:19:14');

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
  `supply_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_no` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iar_items`
--

INSERT INTO `iar_items` (`id`, `iar_id`, `supply_id`, `stock_no`, `unit`, `description`, `quantity`, `unit_cost`, `category_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'STK-001', 'Ream', 'Book paper A4', 5, 198.00, 1, '2026-10-18 05:29:06', '2026-10-18 05:30:18'),
(2, 1, 2, 'STK-002', 'Ream', 'Book paper long', 5, 198.75, 1, '2026-10-18 05:29:06', '2026-10-18 05:30:18'),
(3, 1, 3, 'STK-003', 'Ream', 'Book paper short', 5, 200.00, 1, '2026-10-18 05:29:06', '2026-10-18 05:30:18');

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
  `stocked_in_at` timestamp NULL DEFAULT NULL,
  `stocked_in_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inspection_acceptance_reports`
--

INSERT INTO `inspection_acceptance_reports` (`id`, `iar_no`, `po_id`, `supplier_name`, `po_no`, `date`, `requisitioning_office_dept`, `inspector_name`, `inspector_designation`, `inspection_date`, `inspection_complete`, `inspection_partial`, `acceptor_name`, `acceptor_designation`, `acceptance_date`, `acceptance_complete`, `acceptance_partial`, `status`, `stocked_in_at`, `stocked_in_by_user_id`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'IAR-2026-0001', 1, 'Kristan Educational Supply', 'PO-2026-0001', '2026-10-22', 'Administrative Division', 'ANA FE B. GALANTO', 'Admin. Assistant II', '2026-10-22', 1, 0, 'RHODELIA J. MANDOLADO', 'Admin. Officer IV', '2026-10-22', 1, 0, 'stocked_in', '2026-10-18 05:30:18', 6, '2026-10-18 05:29:06', '2026-10-18 05:30:18', NULL, NULL);

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
(27, '2026_06_10_000001_add_editing_lock_to_purchase_management_tables', 1),
(28, '2026_06_09_000013_add_winning_supplier_id_to_abstracts_of_canvass', 2),
(29, '2026_06_10_000002_add_price_to_requisition_items', 2),
(30, '2026_06_10_000003_add_rc_sequence_to_requisitions', 2),
(31, '2026_06_10_000004_add_user_id_to_requisitions', 2),
(32, '2026_06_10_000005_rename_price_to_unit_price_in_supplies', 2),
(33, '2026_06_14_000001_add_reference_number_to_supplies_table', 2),
(34, '2026_06_14_000002_drop_stock_transactions_table', 2),
(35, '2026_06_15_000001_add_average_unit_cost_and_status_to_supplies_table', 2),
(36, '2026_06_15_000002_create_stock_in_records_table', 2),
(37, '2026_06_20_055923_add_user_approval_status_to_users_table', 2),
(38, '2026_06_20_084202_add_cancelled_by_fields_to_requisitions_table', 2),
(39, '2026_06_28_000001_revert_user_roles_rbac', 2),
(40, '2026_06_29_132628_add_username_to_users_table', 2),
(41, '2026_07_04_000001_add_division_to_users_table', 2),
(42, '2026_07_08_000001_create_rpcis_table', 2),
(43, '2026_07_08_000002_create_rpci_items_table', 2),
(44, '2026_07_12_000001_enhance_iar_items_and_stock_in_records', 2),
(45, '2026_07_12_000002_revise_rpci_tables_for_coa_appendix66', 2),
(46, '2026_07_13_000001_add_stock_in_fields_and_supply_tracking', 2),
(47, '2026_07_13_000002_add_approved_by_to_request_for_quotations', 2),
(48, '2026_07_14_000001_add_category_id_to_po_items_table', 3),
(49, '2026_07_17_000001_add_user_id_to_purchase_requests_table', 4),
(50, '2026_07_18_000001_add_unit_cost_and_total_cost_to_supplies_table', 4),
(51, '2026_07_21_000000_create_notifications_table', 4),
(52, '2026_07_21_000001_add_pending_receipt_to_requisition_status_enum', 4),
(53, '2026_07_21_010000_create_permission_tables', 4),
(54, '2026_07_24_000001_add_status_to_categories_table', 4),
(55, '2026_07_24_000001_add_supplier_id_and_contact_number_to_purchase_orders_table', 4),
(56, '2026_07_24_000002_add_tin_to_suppliers_table', 4),
(57, '2026_07_24_000003_add_supplier_id_and_tin_to_request_for_quotations_table', 4),
(58, '2026_07_30_000001_add_iar_id_to_bac_resolutions_table', 4),
(59, '2026_07_31_000000_create_password_reset_otps_table', 4),
(60, '2026_08_07_000001_add_bac_iar_source_columns', 5),
(61, '2026_08_07_000001_make_rpci_item_count_fields_nullable', 5),
(62, '2026_08_09_000001_backfill_spatie_roles_from_role_column', 5),
(63, '2026_08_09_000002_add_issued_unit_cost_to_requisition_items_table', 6),
(64, '2026_08_24_000001_add_abc_source_to_bac_resolutions_table', 7),
(65, '2026_08_24_000002_add_bac_resolution_source_to_purchase_orders_table', 7),
(66, '2026_08_30_013650_drop_otps_table', 8),
(67, '2026_08_30_013651_drop_contact_person_and_email_from_suppliers_table', 9),
(68, '2026_08_31_041813_add_missing_supply_tracking_columns_to_supplies_table', 10),
(69, '2026_09_06_000001_add_position_to_users_table', 11),
(70, '2026_09_08_000001_add_rejection_fields_to_purchase_requests_table', 12),
(71, '2026_09_09_000001_backfill_abc_committee_designations', 13),
(72, '2026_09_10_000001_drop_approved_by_defaults_from_bac_resolutions', 14),
(73, '2026_09_14_000001_remove_quantity_accepted_rejected_from_iar_items', 15),
(74, '2026_09_15_000001_clear_inventory_rpci_rsmi_requisition_records', 16),
(75, '2026_09_16_000001_backfill_abc_items_from_rfq', 17),
(76, '2026_09_25_000001_remove_username_from_users_table', 18),
(77, '2026_09_26_000001_backfill_requisition_designations_from_user_position', 19),
(78, '2026_09_30_000001_add_supply_id_to_po_items_table', 20),
(79, '2026_10_02_000001_add_division_and_budget_to_bac_resolutions', 21),
(80, '2026_10_02_000002_set_bac_resolution_title_to_sentence_case', 21),
(81, '2026_10_02_000003_widen_bac_resolution_date_to_datetime', 22),
(82, '2026_10_03_123015_change_bac_resolution_date_to_date_only', 23),
(83, '2026_10_08_000001_add_cancellation_remarks_to_requisitions_table', 24),
(84, '2026_10_10_000001_grant_receive_permission_to_supply_officer', 25),
(85, '2026_10_17_000001_add_performance_indexes', 26),
(86, '2026_10_10_000002_clear_all_business_records', 27),
(87, '2026_10_17_000002_clear_all_records_except_users_and_categories', 28);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 6),
(4, 'App\\Models\\User', 2),
(5, 'App\\Models\\User', 4),
(6, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otps`
--

CREATE TABLE `password_reset_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
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
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(2, 'dashboard.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(3, 'dashboard.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(4, 'dashboard.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(5, 'dashboard.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(6, 'users.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(7, 'users.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(8, 'users.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(9, 'users.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(10, 'users.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(11, 'supplies.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(12, 'supplies.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(13, 'supplies.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(14, 'supplies.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(15, 'supplies.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(16, 'suppliers.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(17, 'suppliers.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(18, 'suppliers.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(19, 'suppliers.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(20, 'suppliers.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(21, 'categories.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(22, 'categories.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(23, 'categories.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(24, 'categories.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(25, 'categories.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(26, 'purchase_requests.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(27, 'purchase_requests.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(28, 'purchase_requests.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(29, 'purchase_requests.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(30, 'purchase_requests.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(31, 'request_for_quotations.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(32, 'request_for_quotations.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(33, 'request_for_quotations.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(34, 'request_for_quotations.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(35, 'request_for_quotations.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(36, 'abstracts_of_canvass.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(37, 'abstracts_of_canvass.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(38, 'abstracts_of_canvass.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(39, 'abstracts_of_canvass.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(40, 'abstracts_of_canvass.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(41, 'purchase_orders.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(42, 'purchase_orders.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(43, 'purchase_orders.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(44, 'purchase_orders.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(45, 'purchase_orders.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(46, 'inspection_acceptance_reports.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(47, 'inspection_acceptance_reports.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(48, 'inspection_acceptance_reports.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(49, 'inspection_acceptance_reports.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(50, 'inspection_acceptance_reports.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(51, 'bac_resolutions.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(52, 'bac_resolutions.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(53, 'bac_resolutions.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(54, 'bac_resolutions.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(55, 'bac_resolutions.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(56, 'requisitions.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(57, 'requisitions.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(58, 'requisitions.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(59, 'requisitions.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(60, 'requisitions.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(61, 'rpcis.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(62, 'rpcis.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(63, 'rpcis.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(64, 'rpcis.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(65, 'rpcis.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(66, 'rsmi_reports.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(67, 'rsmi_reports.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(68, 'rsmi_reports.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(69, 'rsmi_reports.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(70, 'rsmi_reports.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(71, 'inventory_reports.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(72, 'inventory_reports.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(73, 'inventory_reports.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(74, 'inventory_reports.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(75, 'inventory_reports.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(76, 'audit_logs.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(77, 'audit_logs.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(78, 'audit_logs.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(79, 'audit_logs.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(80, 'audit_logs.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(81, 'system_settings.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(82, 'system_settings.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(83, 'system_settings.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(84, 'system_settings.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(85, 'system_settings.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(86, 'stock_in.view', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(87, 'stock_in.create', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(88, 'stock_in.update', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(89, 'stock_in.delete', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(90, 'stock_in.approve', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(91, 'requisitions.issue', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(92, 'requisitions.receive', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(93, 'purchase_requests.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(94, 'requisitions.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(95, 'request_for_quotations.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(96, 'abstracts_of_canvass.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(97, 'purchase_orders.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(98, 'inspection_acceptance_reports.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(99, 'bac_resolutions.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(100, 'rpcis.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(101, 'rpcis.preview', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(102, 'inspection_acceptance_reports.stock_in', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(103, 'inventory_reports.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(104, 'rsmi_reports.print', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(105, 'procurement_monitoring_reports.view', 'web', '2026-08-15 01:20:57', '2026-08-15 01:20:57'),
(106, 'procurement_monitoring_reports.create', 'web', '2026-08-15 01:20:57', '2026-08-15 01:20:57'),
(107, 'procurement_monitoring_reports.update', 'web', '2026-08-15 01:20:57', '2026-08-15 01:20:57'),
(108, 'procurement_monitoring_reports.delete', 'web', '2026-08-15 01:20:57', '2026-08-15 01:20:57'),
(109, 'procurement_monitoring_reports.approve', 'web', '2026-08-15 01:20:57', '2026-08-15 01:20:57'),
(110, 'procurement_monitoring_reports.print', 'web', '2026-08-15 01:20:58', '2026-08-15 01:20:58');

-- --------------------------------------------------------

--
-- Table structure for table `po_items`
--

CREATE TABLE `po_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED NOT NULL,
  `stock_no` varchar(255) DEFAULT NULL,
  `supply_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `po_items`
--

INSERT INTO `po_items` (`id`, `po_id`, `stock_no`, `supply_id`, `unit`, `description`, `quantity`, `unit_cost`, `amount`, `category_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 'Ream', 'Book paper A4', 5, 198.00, 990.00, NULL, '2026-10-18 05:14:20', '2026-10-18 05:14:20'),
(2, 1, NULL, NULL, 'Ream', 'Book paper long', 5, 198.75, 993.75, NULL, '2026-10-18 05:14:20', '2026-10-18 05:14:20'),
(3, 1, NULL, NULL, 'Ream', 'Book paper short', 5, 200.00, 1000.00, NULL, '2026-10-18 05:14:20', '2026-10-18 05:14:20');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_no` varchar(255) NOT NULL,
  `abc_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bac_resolution_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resolution_no` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
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

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_no`, `abc_id`, `bac_resolution_id`, `resolution_no`, `supplier_id`, `supplier_name`, `address`, `tin`, `contact_number`, `mode_of_procurement`, `date`, `place_of_delivery`, `delivery_term`, `date_of_delivery`, `payment_term`, `total_amount`, `amount_in_words`, `conforme_name`, `conforme_date`, `authorized_official_name`, `authorized_official_designation`, `funds_available_by`, `funds_available_designation`, `alobs_no`, `alobs_amount`, `status`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'PO-2026-0001', 1, 1, 'BAC RESO-2026-0001', 1, 'Kristan Educational Supply', 'Alunan street, Koronadal City', '123455324556', '09219838679', 'Small Value Procurement (SVP)', '2026-10-21', 'CHR Regional Office XII, Carpenter Hill, Koronadal City', 'Within 7 Calendar Days After Receipt of Purchase Order', '2026-10-22', 'Full Payment Upon Delivery, Inspection, and Acceptance', 2983.75, 'TWO THOUSAND NINE HUNDRED EIGHTY THREE PESOS AND SEVENTY FIVE CENTAVOS ONLY', 'Kristan Educational Supply', NULL, 'Atty. Keysie M. Gomez', 'Authorized Official', 'ANA FE B. GALANTO', 'Admin Asst. II/Budget Officer', NULL, 2983.75, 'draft', '2026-10-18 05:14:20', '2026-10-18 05:14:20', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pr_no` varchar(30) DEFAULT NULL,
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
  `rejection_reason` text DEFAULT NULL,
  `rejected_by_name` varchar(255) DEFAULT NULL,
  `rejected_by_designation` varchar(255) DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`id`, `user_id`, `pr_no`, `date`, `office_division`, `rc_code`, `code`, `name_of_project`, `purpose`, `source_of_fund`, `approved_budget`, `requested_by_name`, `requested_by_designation`, `approved_by_name`, `approved_by_designation`, `status`, `rejection_reason`, `rejected_by_name`, `rejected_by_designation`, `rejected_at`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 2, 'PR-2026-0001', '2026-10-18', 'Administrative Division', '3.1.2', '5020301000', 'Name of Project', 'For office use', 'General Appropriations of Act (GAA)', NULL, 'Sebastian Linugao', 'Administrative Aide I', 'Atty. Keysie M. Gomez', 'Regional Director', 'approved', NULL, NULL, NULL, NULL, '2026-10-18 04:12:28', '2026-10-18 04:30:59', NULL, NULL);

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
(1, 1, NULL, NULL, 'Ream', 'Book paper A4', 5, NULL, '2026-10-18 04:12:28', '2026-10-18 04:12:28', NULL),
(2, 1, NULL, NULL, 'Ream', 'Book paper long', 5, NULL, '2026-10-18 04:12:28', '2026-10-18 04:12:28', NULL),
(3, 1, NULL, NULL, 'Ream', 'Book paper short', 5, NULL, '2026-10-18 04:12:28', '2026-10-18 04:12:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `request_for_quotations`
--

CREATE TABLE `request_for_quotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rfq_no` varchar(255) NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `canvassed_by_name` varchar(255) DEFAULT NULL,
  `canvassed_by_designation` varchar(255) DEFAULT NULL,
  `quoted_by_name` varchar(255) DEFAULT NULL,
  `quoted_by_supplier` varchar(255) DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_by_designation` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `editing_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `editing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_for_quotations`
--

INSERT INTO `request_for_quotations` (`id`, `rfq_no`, `purchase_request_id`, `date`, `supplier_id`, `company_name`, `address`, `contact_number`, `tin`, `canvassed_by_name`, `canvassed_by_designation`, `quoted_by_name`, `quoted_by_supplier`, `approved_by_name`, `approved_by_designation`, `status`, `created_at`, `updated_at`, `editing_by_user_id`, `editing_started_at`) VALUES
(1, 'RFQ-2026-0001', 1, '2026-10-18', NULL, NULL, NULL, NULL, NULL, 'Rodel C. Linugao', 'Administrative Aide-I', NULL, NULL, NULL, NULL, 'draft', '2026-10-18 04:40:10', '2026-10-18 04:40:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--

CREATE TABLE `requisitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `fund_cluster` varchar(255) DEFAULT NULL,
  `division` varchar(255) DEFAULT NULL,
  `division_code` varchar(10) DEFAULT NULL,
  `sequence_number` int(10) UNSIGNED DEFAULT NULL,
  `rc_sequence` int(10) UNSIGNED DEFAULT NULL COMMENT 'Per-division sequence number for Responsibility Center code',
  `responsibility_center_code` varchar(255) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `ris_no` varchar(255) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('requested','approved','issued','pending_receipt','received','cancelled') NOT NULL DEFAULT 'requested',
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
  `cancelled_by_name` varchar(255) DEFAULT NULL,
  `cancelled_by_designation` varchar(255) DEFAULT NULL,
  `cancelled_by_date` date DEFAULT NULL,
  `cancellation_remarks` text DEFAULT NULL,
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
  `price` decimal(12,2) DEFAULT NULL COMMENT 'Unit price at the time of requisition',
  `issued_unit_cost` decimal(12,2) DEFAULT NULL COMMENT 'Moving Average Cost (MAC) snapshot at the time of issuance',
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

--
-- Dumping data for table `rfq_items`
--

INSERT INTO `rfq_items` (`id`, `rfq_id`, `stock_property_no`, `unit`, `item_description`, `quantity`, `unit_cost`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Ream', 'Book paper A4', 5, NULL, '2026-10-18 04:40:10', '2026-10-18 04:40:10'),
(2, 1, NULL, 'Ream', 'Book paper long', 5, NULL, '2026-10-18 04:40:10', '2026-10-18 04:40:10'),
(3, 1, NULL, 'Ream', 'Book paper short', 5, NULL, '2026-10-18 04:40:10', '2026-10-18 04:40:10');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2026-08-07 23:27:45', '2026-08-07 23:27:45'),
(2, 'supply_officer', 'web', '2026-08-07 23:27:46', '2026-08-07 23:27:46'),
(3, 'property_custodian', 'web', '2026-08-07 23:27:46', '2026-08-07 23:27:46'),
(4, 'staff', 'web', '2026-08-07 23:27:46', '2026-08-07 23:27:46'),
(5, 'division_chief', 'web', '2026-08-07 23:27:46', '2026-08-07 23:27:46'),
(6, 'regional_director', 'web', '2026-08-07 23:27:46', '2026-08-07 23:27:46');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(11, 2),
(11, 3),
(11, 4),
(11, 5),
(11, 6),
(12, 1),
(12, 2),
(12, 3),
(13, 1),
(13, 2),
(13, 3),
(14, 1),
(14, 2),
(14, 3),
(15, 1),
(16, 1),
(16, 2),
(16, 3),
(16, 5),
(16, 6),
(17, 1),
(17, 2),
(17, 3),
(18, 1),
(18, 2),
(18, 3),
(19, 1),
(19, 3),
(20, 1),
(21, 1),
(21, 2),
(21, 3),
(21, 5),
(21, 6),
(22, 1),
(22, 2),
(22, 3),
(23, 1),
(23, 2),
(23, 3),
(24, 1),
(24, 3),
(25, 1),
(26, 1),
(26, 2),
(26, 3),
(26, 4),
(26, 5),
(26, 6),
(27, 1),
(27, 2),
(27, 3),
(27, 4),
(27, 5),
(27, 6),
(28, 1),
(28, 2),
(28, 3),
(29, 1),
(29, 3),
(30, 1),
(30, 6),
(31, 1),
(31, 2),
(31, 3),
(31, 4),
(31, 5),
(31, 6),
(32, 1),
(32, 2),
(32, 3),
(33, 1),
(33, 2),
(33, 3),
(34, 1),
(34, 3),
(35, 1),
(36, 1),
(36, 2),
(36, 3),
(36, 4),
(36, 5),
(36, 6),
(37, 1),
(37, 2),
(37, 3),
(38, 1),
(38, 2),
(38, 3),
(39, 1),
(39, 3),
(40, 1),
(41, 1),
(41, 2),
(41, 3),
(41, 4),
(41, 5),
(41, 6),
(42, 1),
(42, 2),
(42, 3),
(43, 1),
(43, 2),
(43, 3),
(44, 1),
(44, 3),
(45, 1),
(46, 1),
(46, 2),
(46, 3),
(46, 4),
(46, 5),
(46, 6),
(47, 1),
(47, 2),
(47, 3),
(48, 1),
(48, 3),
(49, 1),
(49, 3),
(50, 1),
(51, 1),
(51, 2),
(51, 3),
(51, 4),
(51, 5),
(51, 6),
(52, 1),
(52, 2),
(52, 3),
(53, 1),
(53, 2),
(53, 3),
(54, 1),
(54, 3),
(55, 1),
(56, 1),
(56, 2),
(56, 3),
(56, 4),
(56, 5),
(56, 6),
(57, 1),
(57, 2),
(57, 3),
(57, 4),
(57, 5),
(57, 6),
(58, 1),
(58, 2),
(58, 3),
(58, 4),
(58, 5),
(58, 6),
(59, 1),
(60, 1),
(60, 5),
(61, 1),
(61, 2),
(61, 3),
(61, 5),
(61, 6),
(62, 1),
(62, 2),
(62, 3),
(63, 1),
(63, 2),
(63, 3),
(64, 1),
(64, 2),
(64, 3),
(65, 1),
(66, 1),
(66, 2),
(66, 3),
(66, 5),
(66, 6),
(67, 1),
(67, 2),
(67, 3),
(68, 1),
(68, 2),
(68, 3),
(69, 1),
(69, 2),
(69, 3),
(70, 1),
(71, 1),
(71, 2),
(71, 3),
(71, 5),
(71, 6),
(72, 1),
(72, 2),
(72, 3),
(73, 1),
(73, 2),
(73, 3),
(74, 1),
(74, 2),
(74, 3),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(85, 1),
(86, 1),
(86, 2),
(86, 6),
(87, 1),
(87, 2),
(88, 1),
(88, 2),
(89, 1),
(89, 2),
(90, 1),
(91, 1),
(91, 2),
(92, 1),
(92, 2),
(92, 4),
(92, 5),
(92, 6),
(93, 1),
(93, 2),
(93, 3),
(93, 6),
(94, 1),
(94, 2),
(94, 3),
(94, 4),
(94, 5),
(94, 6),
(95, 1),
(95, 2),
(95, 3),
(95, 6),
(96, 1),
(96, 2),
(96, 3),
(96, 6),
(97, 1),
(97, 2),
(97, 3),
(97, 6),
(98, 1),
(98, 2),
(98, 3),
(98, 6),
(99, 1),
(99, 2),
(99, 3),
(99, 6),
(100, 1),
(100, 2),
(100, 3),
(101, 1),
(101, 2),
(101, 3),
(102, 1),
(102, 2),
(102, 3),
(103, 1),
(103, 2),
(103, 3),
(104, 1),
(104, 2),
(104, 3),
(105, 1),
(105, 2),
(105, 5),
(105, 6),
(106, 1),
(107, 1),
(108, 1),
(109, 1),
(110, 1),
(110, 2);

-- --------------------------------------------------------

--
-- Table structure for table `rpcis`
--

CREATE TABLE `rpcis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_no` varchar(255) DEFAULT NULL,
  `inventory_type` varchar(255) DEFAULT NULL,
  `report_date` date NOT NULL,
  `fund_cluster` varchar(255) DEFAULT NULL,
  `accountable_person` varchar(255) DEFAULT NULL,
  `accountable_position` varchar(255) DEFAULT NULL,
  `accountability_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `chairman_name` varchar(255) DEFAULT NULL,
  `member_one_name` varchar(255) DEFAULT NULL,
  `member_two_name` varchar(255) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `approved_position` varchar(255) DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL,
  `verified_position` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rpci_items`
--

CREATE TABLE `rpci_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rpci_id` bigint(20) UNSIGNED NOT NULL,
  `supply_id` bigint(20) UNSIGNED DEFAULT NULL,
  `article` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock_number` varchar(255) DEFAULT NULL,
  `unit_of_measure` varchar(255) DEFAULT NULL,
  `unit_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_per_card` int(11) NOT NULL DEFAULT 0,
  `on_hand_per_count` int(11) DEFAULT 0,
  `shortage_quantity` int(11) DEFAULT 0,
  `shortage_value` decimal(15,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
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
('le43sJZBRFBKAXgP64dQjqq5ssqPhx4xJh8Sojij', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiVlRGVmEyVmVvRHh5Zm4xekFlN3NDV1czVXhJM1hvTmRwdmRQTTFBYSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWRtaW4iO3M6NToicm91dGUiO3M6MzA6ImZpbGFtZW50LmFkbWluLnBhZ2VzLmRhc2hib2FyZCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImNiZDdjNmMzMDcwNmFmNjU0Y2ZlNDE2NDQ4YjdhMTgyMTc4OGZlYjc4ZGEwNWY4OTU0OWRhZGMyYWU3MGJlZDciO30=', 1792466930);

-- --------------------------------------------------------

--
-- Table structure for table `stock_in_records`
--

CREATE TABLE `stock_in_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `iar_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_number` varchar(50) NOT NULL,
  `supply_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `date_received` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_in_records`
--

INSERT INTO `stock_in_records` (`id`, `iar_id`, `reference_number`, `supply_id`, `quantity_received`, `unit_cost`, `total_cost`, `supplier`, `date_received`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'PO-2026-0001', 1, 5, 198.00, 990.00, 'Kristan Educational Supply', '2026-10-22', 'Auto stock-in from IAR IAR-2026-0001', '2026-10-18 05:30:18', '2026-10-18 05:30:18'),
(2, 1, 'PO-2026-0002', 2, 5, 198.75, 993.75, 'Kristan Educational Supply', '2026-10-22', 'Auto stock-in from IAR IAR-2026-0001', '2026-10-18 05:30:18', '2026-10-18 05:30:18'),
(3, 1, 'PO-2026-0003', 3, 5, 200.00, 1000.00, 'Kristan Educational Supply', '2026-10-22', 'Auto stock-in from IAR IAR-2026-0001', '2026-10-18 05:30:18', '2026-10-18 05:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `tin` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `tin`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Kristan Educational Supply', '09219838679', '123455324556', 'Alunan street, Koronadal City', '2026-10-18 05:14:20', '2026-10-18 05:14:20');

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
  `unit_price` decimal(12,2) DEFAULT NULL COMMENT 'Unit cost / price per item',
  `average_unit_cost` decimal(12,2) DEFAULT NULL COMMENT 'Moving Average Cost calculated from stock receipts',
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Moving Average Cost (MAC) ??? system-maintained',
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Total inventory value = current_stock ?? unit_cost',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'Active, Inactive, Discontinued',
  `reference_number` varchar(255) DEFAULT NULL,
  `last_received_date` date DEFAULT NULL,
  `latest_reference_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplies`
--

INSERT INTO `supplies` (`id`, `stock_no`, `category_id`, `supplier_id`, `name`, `description`, `unit`, `reorder_level`, `current_stock`, `unit_price`, `average_unit_cost`, `unit_cost`, `total_cost`, `status`, `reference_number`, `last_received_date`, `latest_reference_number`, `created_at`, `updated_at`) VALUES
(1, 'STK-001', 1, NULL, 'Book paper A4', NULL, 'Ream', 2, 5, NULL, 198.00, 198.00, 990.00, 'active', NULL, '2026-10-22', 'IAR-2026-0001', '2026-10-18 05:30:18', '2026-10-18 05:32:04'),
(2, 'STK-002', 1, NULL, 'Book paper long', NULL, 'Ream', 2, 5, NULL, 198.75, 198.75, 993.75, 'active', NULL, '2026-10-22', 'IAR-2026-0001', '2026-10-18 05:30:18', '2026-10-18 05:31:44'),
(3, 'STK-003', 1, NULL, 'Book paper short', NULL, 'Ream', 2, 5, NULL, 200.00, 200.00, 1000.00, 'active', NULL, '2026-10-22', 'IAR-2026-0001', '2026-10-18 05:30:18', '2026-10-18 05:31:25');

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
  `division` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','deactivated') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `role`, `division`, `position`, `email_verified_at`, `password`, `remember_token`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Rodel Linugao', 'rodellinugao@gmail.com', '09219838679', 'admin', NULL, 'Administrative Officer IV', NULL, '$2y$12$9QliX5nZJERHhiEKXIKBd.VeRUv4LNxxYjhh3GCe5V.mwIQhnE1J2', 'swOHAW7MUg3mtL1AD9cp5vEZLr9LU2ZkpIvE1N156CMf2C6jXdmVNSS4PPx7', 'approved', '2026-06-12 11:31:52', '2026-09-07 04:40:16'),
(2, 'Sebastian Linugao', 'baste@gmail.com', '09396378985', 'staff', 'Administrative', 'Administrative Aide I', NULL, '$2y$12$RVDOl90i1xcRwaiFoqmvXOblfa57.XA3PXQVy8qYrnS/9m2UH5tom', 'IjpU9PWLAAwUck5xvktvuSIIKVQdgBrUJPvOKER6lwgfAGiNhh612a1iYBmg', 'approved', '2026-06-12 14:01:09', '2026-09-07 04:45:00'),
(3, 'Atty. Keysie M. Gomez', 'director@example.com', '+639396378986', 'regional_director', NULL, 'Regional Director', NULL, '$2y$12$Nd2PL0sK6HWfdXGRUbYAkuoPqqGYVZoD/h8PHjW0DYEGaZWncC6SO', NULL, 'approved', '2026-06-19 16:29:27', '2026-09-07 04:41:10'),
(4, 'Jeslie C. Tanghinan', 'jeslie@gmal.com', '+639396378987', 'division_chief', 'Investigation', 'Special Investigator IV', NULL, '$2y$12$sUvLrww1HWeko51Nm7UuOuQMz8873kUdUs7AuRMJ0kNxBpzf3KXFS', NULL, 'approved', '2026-06-26 10:27:52', '2026-09-07 04:43:22'),
(6, 'Ronalyn A. Sorongon', 'sorongonronalyn@gmail.com', '09396378986', 'supply_officer', NULL, 'Administrative Aide VI', NULL, '$2y$12$PSuAi63wDvWGYT95.BT1xOCTrmIRIQDU0YqA7oCygEbTOmuAoe//S', NULL, 'approved', '2026-07-03 15:14:37', '2026-09-07 04:39:21');

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
  ADD KEY `abstracts_of_canvass_editing_by_user_id_foreign` (`editing_by_user_id`),
  ADD KEY `abstracts_of_canvass_winning_supplier_id_foreign` (`winning_supplier_id`),
  ADD KEY `abstracts_of_canvass_status_idx` (`status`);

--
-- Indexes for table `bac_resolutions`
--
ALTER TABLE `bac_resolutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bac_resolutions_resolution_no_unique` (`resolution_no`),
  ADD UNIQUE KEY `bac_resolutions_iar_id_unique` (`iar_id`),
  ADD UNIQUE KEY `bac_resolutions_abc_id_unique` (`abc_id`),
  ADD KEY `bac_resolutions_editing_by_user_id_foreign` (`editing_by_user_id`),
  ADD KEY `bac_resolutions_status_idx` (`status`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `categories_status_idx` (`status`);

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
  ADD KEY `iar_items_iar_id_foreign` (`iar_id`),
  ADD KEY `iar_items_supply_id_foreign` (`supply_id`),
  ADD KEY `iar_items_category_id_foreign` (`category_id`);

--
-- Indexes for table `inspection_acceptance_reports`
--
ALTER TABLE `inspection_acceptance_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inspection_acceptance_reports_iar_no_unique` (`iar_no`),
  ADD KEY `inspection_acceptance_reports_po_id_foreign` (`po_id`),
  ADD KEY `inspection_acceptance_reports_editing_by_user_id_foreign` (`editing_by_user_id`),
  ADD KEY `inspection_acceptance_reports_stocked_in_by_user_id_foreign` (`stocked_in_by_user_id`),
  ADD KEY `inspection_acceptance_reports_status_idx` (`status`);

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
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  ADD KEY `notifications_read_at_idx` (`read_at`);

--
-- Indexes for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `password_reset_otps_user_id_index` (`user_id`),
  ADD KEY `password_reset_otps_email_index` (`email`),
  ADD KEY `password_reset_otps_expires_at_index` (`expires_at`),
  ADD KEY `password_reset_otps_is_used_index` (`is_used`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `po_items`
--
ALTER TABLE `po_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_items_po_id_foreign` (`po_id`),
  ADD KEY `po_items_category_id_foreign` (`category_id`),
  ADD KEY `po_items_supply_id_foreign` (`supply_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_no_unique` (`po_no`),
  ADD UNIQUE KEY `purchase_orders_bac_resolution_id_unique` (`bac_resolution_id`),
  ADD KEY `purchase_orders_abc_id_foreign` (`abc_id`),
  ADD KEY `purchase_orders_editing_by_user_id_foreign` (`editing_by_user_id`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_status_idx` (`status`),
  ADD KEY `purchase_orders_date_idx` (`date`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_requests_pr_no_unique` (`pr_no`),
  ADD KEY `purchase_requests_editing_by_user_id_foreign` (`editing_by_user_id`),
  ADD KEY `purchase_requests_user_id_foreign` (`user_id`),
  ADD KEY `purchase_requests_status_idx` (`status`),
  ADD KEY `purchase_requests_created_at_idx` (`created_at`),
  ADD KEY `purchase_requests_user_id_status_idx` (`user_id`,`status`);

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
  ADD KEY `request_for_quotations_editing_by_user_id_foreign` (`editing_by_user_id`),
  ADD KEY `request_for_quotations_supplier_id_foreign` (`supplier_id`),
  ADD KEY `request_for_quotations_status_idx` (`status`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisitions_user_id_foreign` (`user_id`),
  ADD KEY `requisitions_status_idx` (`status`),
  ADD KEY `requisitions_created_at_idx` (`created_at`),
  ADD KEY `requisitions_status_issued_by_date_idx` (`status`,`issued_by_date`),
  ADD KEY `requisitions_user_id_status_idx` (`user_id`,`status`);

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
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `rpcis`
--
ALTER TABLE `rpcis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rpcis_report_no_unique` (`report_no`),
  ADD KEY `rpcis_user_id_foreign` (`user_id`),
  ADD KEY `rpcis_status_idx` (`status`),
  ADD KEY `rpcis_report_date_idx` (`report_date`);

--
-- Indexes for table `rpci_items`
--
ALTER TABLE `rpci_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpci_items_rpci_id_foreign` (`rpci_id`),
  ADD KEY `rpci_items_supply_id_foreign` (`supply_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_in_records`
--
ALTER TABLE `stock_in_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_in_records_reference_number_unique` (`reference_number`),
  ADD KEY `stock_in_records_supply_id_foreign` (`supply_id`),
  ADD KEY `stock_in_records_iar_id_foreign` (`iar_id`),
  ADD KEY `stock_in_records_date_received_idx` (`date_received`),
  ADD KEY `stock_in_records_supply_id_date_received_idx` (`supply_id`,`date_received`);

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
  ADD KEY `supplies_supplier_id_foreign` (`supplier_id`),
  ADD KEY `supplies_status_idx` (`status`),
  ADD KEY `supplies_name_idx` (`name`),
  ADD KEY `supplies_category_id_status_idx` (`category_id`,`status`),
  ADD KEY `supplies_supplier_id_status_idx` (`supplier_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_status_idx` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `abc_items`
--
ALTER TABLE `abc_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iar_items`
--
ALTER TABLE `iar_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `po_items`
--
ALTER TABLE `po_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `request_for_quotations`
--
ALTER TABLE `request_for_quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rpcis`
--
ALTER TABLE `rpcis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rpci_items`
--
ALTER TABLE `rpci_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_in_records`
--
ALTER TABLE `stock_in_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  ADD CONSTRAINT `abstracts_of_canvass_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `request_for_quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `abstracts_of_canvass_winning_supplier_id_foreign` FOREIGN KEY (`winning_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bac_resolutions`
--
ALTER TABLE `bac_resolutions`
  ADD CONSTRAINT `bac_resolutions_abc_id_foreign` FOREIGN KEY (`abc_id`) REFERENCES `abstracts_of_canvass` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bac_resolutions_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bac_resolutions_iar_id_foreign` FOREIGN KEY (`iar_id`) REFERENCES `inspection_acceptance_reports` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `iar_items`
--
ALTER TABLE `iar_items`
  ADD CONSTRAINT `iar_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `iar_items_iar_id_foreign` FOREIGN KEY (`iar_id`) REFERENCES `inspection_acceptance_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `iar_items_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inspection_acceptance_reports`
--
ALTER TABLE `inspection_acceptance_reports`
  ADD CONSTRAINT `inspection_acceptance_reports_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inspection_acceptance_reports_po_id_foreign` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inspection_acceptance_reports_stocked_in_by_user_id_foreign` FOREIGN KEY (`stocked_in_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD CONSTRAINT `password_reset_otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `po_items`
--
ALTER TABLE `po_items`
  ADD CONSTRAINT `po_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `po_items_po_id_foreign` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_items_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_abc_id_foreign` FOREIGN KEY (`abc_id`) REFERENCES `abstracts_of_canvass` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_bac_resolution_id_foreign` FOREIGN KEY (`bac_resolution_id`) REFERENCES `bac_resolutions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_editing_by_user_id_foreign` FOREIGN KEY (`editing_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `request_for_quotations_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `request_for_quotations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD CONSTRAINT `requisitions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rpcis`
--
ALTER TABLE `rpcis`
  ADD CONSTRAINT `rpcis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rpci_items`
--
ALTER TABLE `rpci_items`
  ADD CONSTRAINT `rpci_items_rpci_id_foreign` FOREIGN KEY (`rpci_id`) REFERENCES `rpcis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rpci_items_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_in_records`
--
ALTER TABLE `stock_in_records`
  ADD CONSTRAINT `stock_in_records_iar_id_foreign` FOREIGN KEY (`iar_id`) REFERENCES `inspection_acceptance_reports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_in_records_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE;

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
