-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 26, 2026 at 01:34 PM
-- Server version: 11.4.12-MariaDB-cll-lve
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meakncva_mk`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` int(11) DEFAULT 0,
  `id_schools` int(11) NOT NULL,
  `school_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`id`, `username`, `password`, `user_type`, `id_schools`, `school_id`) VALUES
(1, 'adminmeakea', '$2y$10$PXrmRd47yuhGIABa6SPENOaDLY7v4Pc.QznzcShCRn0C9N3gAYlYu', 1, 0, 0),
(2, 'sokea', '$2y$10$XMFth0PvkjDaAvGh3JQqHeMlv6TWw9qkXyZfyu4T0/YDx5LQZQnE2', 0, 0, 0),
(3, 'meakea', '$2y$10$F00ychl.jeA0zT9opusXkuzMqZV80jnICKPqr8vRb3wRZC9TaM5Dq', 1, 0, 0),
(7, 'chandy', '$2y$10$/N.pWH5IfB9UqDtlcsV6ReEs6UgnXse/JO0Olmp4Dr4NiH8zNAxGu', 1, 0, 2),
(8, 'dy-ii', '$2y$10$MbO5YneK.8DnA0mUsJsnAeP0MJbxjmMhFjA2GpJkg2pnEcBWvj7Zy', 0, 0, 2),
(9, 'sokea-ii', '$2y$10$WnK.0pxRJczRHpLCNIHp.emH8yd9wYHR6sJTAEa5XxdxPUKCSKsRS', 0, 0, 2),
(14, 'thokphally', '$2y$10$RYcR9/jqW6D5LLh5njVtt.vMzTAoHeFboG3z2c3gE1z42S6ZFmnC.', 0, 0, 0),
(11, 'sarak', '$2y$10$jdB2HkEESMj3uhOP8ST1xuACnaJCcrBim/kYgidgikeSWK4USRRpS', 0, 0, 1),
(12, 'meakea1', '$2y$10$.YhT7qtR3GSiL/QNKlPlTOYvK.Exi6aLBVjHAc/drbgvqspkoA27q', 1, 0, 1),
(13, 'meakea2', '$2y$10$kl8mimZbPVBjk9imXDRX4.gIEBA1qFETJ.XSr.1DrRASgG5fskBU6', 0, 0, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
