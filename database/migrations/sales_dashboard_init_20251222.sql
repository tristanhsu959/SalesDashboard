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
