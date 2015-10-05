# Simple Whitelist Manager

一个超简陋的网页端白名单管理程序，方便操作Minecraft服务器的白名单。

需要配合AutoWhitelist插件一起使用。

-------------
需求

1. AutoWhitelist插件(http://dev.bukkit.org/bukkit-plugins/autowhitelist/)
2. Web服务器+MYSQL+PHP5运行环境

-------------
安装

1. MC服务器端安装AutoWhitelist插件。
2. 创建表

    `CREATE TABLE `tbl_users` (
	`uid` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`email` VARCHAR(255) NULL DEFAULT NULL,
	`date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`uid`),
	UNIQUE INDEX `name` (`name`)
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
AUTO_INCREMENT=1
;
`
3. 编辑AutoWhitelist插件的配置文件，将SqlEnable更改为true，并填写数据库信息。
4. 编辑whitelist_manager.php 配置好数据库信息和登录密码。
