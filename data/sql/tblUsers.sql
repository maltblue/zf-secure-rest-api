SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `tblUsers`
-- ----------------------------
DROP TABLE IF EXISTS `tblUsers`;
CREATE TABLE `tblUsers` (
  `apikey` varchar(250) NOT NULL,
  `name` varchar(200) NOT NULL,
  `secretkey` varchar(250) NOT NULL,
  `email` varchar(200) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `tblUsers`
-- ----------------------------
BEGIN;
INSERT INTO `tblUsers` VALUES ('oks5ath9lid6nil3n', 'generic user', 'vog4muf6cav2lyt8f', 'user@example.com');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
