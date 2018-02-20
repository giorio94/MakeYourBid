SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS `MakeYourBid`;
CREATE DATABASE `MakeYourBid`;

CREATE TABLE `bid` (
  `bid_product` int(11) NOT NULL,
  `bid_user` varchar(255) COLLATE utf8_bin NOT NULL,
  `bid_thr` decimal(11,2) UNSIGNED NOT NULL,
  `bid_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bid_product`,`bid_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `product_auction` (
  `pra_id` int(11) NOT NULL,
  `pra_bid` decimal(11,2) UNSIGNED NOT NULL DEFAULT '1.00',
  `pra_user` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `pra_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `product_description` (
  `pr_id` int(11) NOT NULL,
  `pr_title` varchar(40) COLLATE utf8_bin NOT NULL,
  `pr_subtitle` varchar(100) COLLATE utf8_bin NOT NULL,
  `pr_description` varchar(1000) COLLATE utf8_bin NOT NULL,
  `pr_image` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`pr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `user` (
  `u_email` varchar(255) COLLATE utf8_bin NOT NULL,
  `u_password` char(64) COLLATE utf8_bin NOT NULL,
  `u_salt` char(16) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`u_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `product_auction` (`pra_id`, `pra_bid`, `pra_user`, `pra_date`) VALUES
(1, '1.00', NULL, CURRENT_TIMESTAMP);

INSERT INTO `product_description` (`pr_id`, `pr_title`, `pr_subtitle`, `pr_description`, `pr_image`) VALUES
(1, 'One hundred trillion dollars banknote', 'of Zimbabwe', '<p class=\"text\">What is a trillion? Well it is a million of millions, or in other words, a number made by a one followed by twelve zeros.</p><p class=\"text\">So, what kind of a banknote ever printed has, <i>eh...</i>, fourteen zeros on it? I know you immediately thought about what would be your first purchase with such a <span class=\"spaced\">HUGE</span> amount of money in your hands: your favorite sport car? No! What a shame! You would be able to buy the entire car company with no more than a corner of this piece of paper.</p><p class=\"text\">You would have, if they only had been US dollars. Anyway, don\'t waste the chance to get your hands on this relic: it is perfect both as a gift and to be exhibited on your desk as a fantastic conversation starter or to drive your daydreams.</p>', 'img/zimbabwe_trillion_banknote.jpg');

ALTER TABLE `bid`
  ADD FOREIGN KEY (`bid_product`) REFERENCES `product_auction` (`pra_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`bid_user`) REFERENCES `user` (`u_email`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `product_auction`
  ADD FOREIGN KEY (`pra_id`) REFERENCES `product_description` (`pr_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD FOREIGN KEY (`pra_user`) REFERENCES `user` (`u_email`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;
