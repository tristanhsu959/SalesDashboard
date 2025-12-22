-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-21 03:58:07
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `sales_dashboard`
--

-- --------------------------------------------------------

--
-- 資料表結構 `braised_gravy`
--

CREATE TABLE `braised_gravy` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shopId` varchar(6) NOT NULL,
  `shopName` varchar(30) NOT NULL,
  `areaId` tinyint(4) NOT NULL,
  `qty` int(11) NOT NULL,
  `saleDate` date NOT NULL,
  `updateAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='秘製滷肉汁';

-- --------------------------------------------------------

--
-- 資料表結構 `braised_pork`
--

CREATE TABLE `braised_pork` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shopId` varchar(6) NOT NULL,
  `shopName` varchar(30) NOT NULL,
  `areaId` tinyint(4) NOT NULL,
  `qty` int(11) NOT NULL,
  `saleDate` date NOT NULL,
  `updateAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='主廚秘製滷肉飯';

-- --------------------------------------------------------

--
-- 資料表結構 `egg_tofu`
--

CREATE TABLE `egg_tofu` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shopId` varchar(6) NOT NULL,
  `shopName` varchar(30) NOT NULL,
  `areaId` tinyint(4) NOT NULL,
  `qty` int(11) NOT NULL,
  `saleDate` date NOT NULL,
  `updateAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='老皮嫩肉';

-- --------------------------------------------------------

--
-- 資料表結構 `pork_ribs`
--

CREATE TABLE `pork_ribs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shopId` varchar(6) NOT NULL,
  `shopName` varchar(30) NOT NULL,
  `areaId` tinyint(4) NOT NULL,
  `qty` int(11) NOT NULL,
  `saleDate` date NOT NULL,
  `updateAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='橙汁排骨';

-- --------------------------------------------------------

--
-- 資料表結構 `role`
--

CREATE TABLE `role` (
  `roleId` int(10) UNSIGNED NOT NULL,
  `roleName` varchar(20) NOT NULL,
  `roleGroup` tinyint(3) UNSIGNED NOT NULL,
  `rolePermission` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `roleArea` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `createAt` datetime NOT NULL,
  `updateAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `role`
--

INSERT INTO `role` (`roleId`, `roleName`, `roleGroup`, `rolePermission`, `roleArea`, `createAt`, `updateAt`) VALUES
(1, 'Supervisor', 1, '{\"porkRibs\":[\"2\"],\"tomatoBeef\":[\"2\"],\"braisedPork\":[\"2\"],\"eggTofu\":[\"2\"],\"braisedGravy\":[\"2\"],\"users\":[\"2\",\"1\",\"3\",\"4\"],\"roles\":[\"2\",\"1\",\"3\",\"4\"]}', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', '2025-12-16 15:04:21', '2025-12-18 23:44:50'),
(2, '新品測試1', 3, NULL, NULL, '2025-12-16 15:15:58', '2025-12-17 16:59:03'),
(4, 'TEST5', 3, '{\"braisedPork\":[\"2\"]}', '[\"2\",\"3\"]', '2025-12-17 16:58:13', '2025-12-18 23:03:49'),
(5, 'TESTA', 2, '{\"porkRibs\":[\"2\"],\"tomatoBeef\":[\"2\"],\"braisedPork\":[\"2\"],\"eggTofu\":[\"2\"],\"braisedGravy\":[\"2\"],\"users\":[\"2\",\"1\",\"3\",\"4\"],\"roles\":[\"2\",\"1\",\"3\",\"4\"]}', '[]', '2025-12-18 17:09:00', '2025-12-18 17:09:00'),
(6, 'TEST per', 2, '{\"braisedPork\":[\"2\"],\"eggTofu\":[\"2\"],\"braisedGravy\":[\"2\"],\"users\":[\"2\",\"1\",\"3\",\"4\"],\"roles\":[\"2\",\"1\",\"3\",\"4\"]}', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', '2025-12-18 21:14:25', '2025-12-18 21:14:25');

-- --------------------------------------------------------

--
-- 資料表結構 `tomatobeef`
--

CREATE TABLE `tomatobeef` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shopId` varchar(6) NOT NULL,
  `shopName` varchar(30) NOT NULL,
  `areaId` tinyint(4) NOT NULL,
  `qty` int(11) NOT NULL,
  `saleDate` date NOT NULL,
  `updateAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='蕃茄牛三寶';

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `userId` int(10) UNSIGNED NOT NULL,
  `userAd` varchar(20) NOT NULL,
  `userDisplayName` varchar(20) DEFAULT NULL,
  `userRoleId` tinyint(3) UNSIGNED NOT NULL,
  `createAt` datetime NOT NULL,
  `updateAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `user`
--

INSERT INTO `user` (`userId`, `userAd`, `userDisplayName`, `userRoleId`, `createAt`, `updateAt`) VALUES
(1, 'tristan.hsu', 'Tristan', 1, '2025-12-16 15:01:57', '2025-12-16 15:44:30'),
(2, 'TESt.1', 'test1', 2, '2025-12-16 15:41:42', '2025-12-16 15:41:42'),
(4, 'test.a', 'aaa', 4, '2025-12-18 23:13:08', '2025-12-18 23:13:08');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `braised_gravy`
--
ALTER TABLE `braised_gravy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saleDate` (`saleDate`),
  ADD KEY `areaId` (`areaId`);

--
-- 資料表索引 `braised_pork`
--
ALTER TABLE `braised_pork`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saleDate` (`saleDate`),
  ADD KEY `areaId` (`areaId`);

--
-- 資料表索引 `egg_tofu`
--
ALTER TABLE `egg_tofu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saleDate` (`saleDate`),
  ADD KEY `areaId` (`areaId`);

--
-- 資料表索引 `pork_ribs`
--
ALTER TABLE `pork_ribs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saleDate` (`saleDate`),
  ADD KEY `areaId` (`areaId`);

--
-- 資料表索引 `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`roleId`);

--
-- 資料表索引 `tomatobeef`
--
ALTER TABLE `tomatobeef`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saleDate` (`saleDate`),
  ADD KEY `areaId` (`areaId`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userId`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `braised_gravy`
--
ALTER TABLE `braised_gravy`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `braised_pork`
--
ALTER TABLE `braised_pork`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `egg_tofu`
--
ALTER TABLE `egg_tofu`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `pork_ribs`
--
ALTER TABLE `pork_ribs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `role`
--
ALTER TABLE `role`
  MODIFY `roleId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `tomatobeef`
--
ALTER TABLE `tomatobeef`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `userId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
