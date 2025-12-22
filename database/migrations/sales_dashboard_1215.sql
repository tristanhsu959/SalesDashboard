-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-16 08:14:17
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
CREATE DATABASE IF NOT EXISTS `sales_dashboard` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sales_dashboard`;

-- --------------------------------------------------------

--
-- 資料表結構 `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `RoleId` int(10) UNSIGNED NOT NULL,
  `RoleName` varchar(20) NOT NULL,
  `RoleGroup` tinyint(3) UNSIGNED NOT NULL,
  `CreateAt` datetime NOT NULL,
  `UpdateAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `role`
--

INSERT INTO `role` (`RoleId`, `RoleName`, `RoleGroup`, `CreateAt`, `UpdateAt`) VALUES
(1, 'Supervisor', 1, '2025-12-16 15:04:21', '2025-12-16 15:04:21');

-- --------------------------------------------------------

--
-- 資料表結構 `rolepermission`
--

DROP TABLE IF EXISTS `rolepermission`;
CREATE TABLE `rolepermission` (
  `RoleId` int(10) UNSIGNED NOT NULL,
  `Permission` char(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `rolepermission`
--

INSERT INTO `rolepermission` (`RoleId`, `Permission`) VALUES
(1, '7FFFFFFF');

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

DROP TABLE IF EXISTS `user`;
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
(1, 'tristan.hsu', 'Tristan', NULL, 1, '2025-12-16 15:01:57', '2025-12-16 15:01:57');

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
  MODIFY `RoleId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `UserId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
