CREATE TABLE `mmoney_company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `company_name` varchar(100) NOT NULL COMMENT '企业名称',
  `license_number` varchar(50) NOT NULL COMMENT '执照号',
  `contact_person` varchar(50) NOT NULL COMMENT '企业联系人',
  `address` varchar(255) NOT NULL COMMENT '企业地址',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业基本资料表'; 