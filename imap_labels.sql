SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

CREATE TABLE `imap_labels` (
  `labelId` bigint(20) UNSIGNED NOT NULL,
  `userId` int(10) UNSIGNED NOT NULL,
  `label` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `red` tinyint(3) UNSIGNED NOT NULL,
  `green` tinyint(3) UNSIGNED NOT NULL,
  `blue` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `imap_labels`
  ADD PRIMARY KEY (`labelId`),
  ADD UNIQUE KEY `userId` (`userId`,`label`);

ALTER TABLE `imap_labels`
  MODIFY `labelId` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `imap_labels`
  ADD CONSTRAINT `imap_labels_user` FOREIGN KEY (`userId`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

COMMIT;