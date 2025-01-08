-- 企业表
CREATE TABLE `mmoney_company` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `company_name` varchar(100) NOT NULL COMMENT '企业名称',
    `company_code` varchar(50) NOT NULL COMMENT '企业代码',
    `contact_name` varchar(50) NOT NULL COMMENT '联系人',
    `contact_phone` varchar(20) NOT NULL COMMENT '联系电话',
    `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
    `address` varchar(255) DEFAULT NULL COMMENT '地址',
    `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
    `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
    `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_code` (`company_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业表';

-- 企业员工表
CREATE TABLE `mmoney_company_user` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `company_id` int(10) unsigned NOT NULL COMMENT '所属企业ID',
    `user_id` int(10) unsigned NOT NULL COMMENT 'FA会员ID',
    `realname` varchar(50) NOT NULL COMMENT '真实姓名',
    `position` varchar(100) DEFAULT NULL COMMENT '职务',
    `role` enum('admin','staff') DEFAULT 'staff' COMMENT '角色',
    `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
    `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
    `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `company_user_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `mmoney_company` (`id`) ON DELETE CASCADE,
    CONSTRAINT `company_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `mmoney_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业员工表'; 