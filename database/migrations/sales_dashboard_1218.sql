-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-18 10:53:39
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
-- 資料表結構 `role`
--

CREATE TABLE `role` (
  `RoleId` int(10) UNSIGNED NOT NULL,
  `RoleName` varchar(20) NOT NULL,
  `RoleGroup` tinyint(3) UNSIGNED NOT NULL,
  `RolePermission` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`RolePermission`)),
  `RoleArea` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`RoleArea`)),
  `CreateAt` datetime NOT NULL,
  `UpdateAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `role`
--

INSERT INTO `role` (`RoleId`, `RoleName`, `RoleGroup`, `RolePermission`, `RoleArea`, `CreateAt`, `UpdateAt`) VALUES
(1, 'Supervisor', 1, '{\"porkRibs\":[2],\"tomatoBeef\":[2],\"users\":[1,2,3,4],\"roles\":[1,2,3,4]}', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', '2025-12-16 15:04:21', '2025-12-16 15:04:21'),
(2, '新品測試1', 3, NULL, NULL, '2025-12-16 15:15:58', '2025-12-17 16:59:03'),
(4, 'TEST5', 3, NULL, NULL, '2025-12-17 16:58:13', '2025-12-17 16:58:13'),
(5, 'TESTA', 2, '{\"porkRibs\":[\"2\"],\"tomatoBeef\":[\"2\"],\"braisedPork\":[\"2\"],\"eggTofu\":[\"2\"],\"braisedGravy\":[\"2\"],\"users\":[\"2\",\"1\",\"3\",\"4\"],\"roles\":[\"2\",\"1\",\"3\",\"4\"]}', '[]', '2025-12-18 17:09:00', '2025-12-18 17:09:00');

-- --------------------------------------------------------

--
-- 資料表結構 `rolepermission`
--

CREATE TABLE `rolepermission` (
  `RoleId` int(10) UNSIGNED NOT NULL,
  `Permission` char(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `rolepermission`
--

INSERT INTO `rolepermission` (`RoleId`, `Permission`) VALUES
(1, '7FFFFFFF'),
(2, '01010002'),
(2, '01100002'),
(4, '01020002'),
(4, '01040002'),
(4, '01080002');

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `UserId` int(10) UNSIGNED NOT NULL,
  `UserAd` varchar(20) NOT NULL,
  `UserDisplayName` varchar(20) DEFAULT NULL,
  `UserAreaId` varchar(50) DEFAULT NULL,
  `UserRoleId` tinyint(3) UNSIGNED NOT NULL,
  `CreateAt` datetime NOT NULL,
  `UpdateAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `user`
--

INSERT INTO `user` (`UserId`, `UserAd`, `UserDisplayName`, `UserAreaId`, `UserRoleId`, `CreateAt`, `UpdateAt`) VALUES
(1, 'tristan.hsu', 'Tristan', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', 1, '2025-12-16 15:01:57', '2025-12-16 15:44:30'),
(2, 'TESt.1', 'test1', '[\"1\",\"2\",\"3\"]', 2, '2025-12-16 15:41:42', '2025-12-16 15:41:42');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`RoleId`);

--
-- 資料表索引 `rolepermission`
--
ALTER TABLE `rolepermission`
  ADD PRIMARY KEY (`RoleId`,`Permission`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserId`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `role`
--
ALTER TABLE `role`
  MODIFY `RoleId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `UserId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
