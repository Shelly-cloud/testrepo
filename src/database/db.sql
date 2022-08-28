CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL COMMENT 'username of user',
  `githubUername` varchar(50) DEFAULT NULL COMMENT 'githubUername of user',
  `githubUernameAuthToken` varchar(50) DEFAULT NULL COMMENT 'Github auth Token with the required access for repo',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(4) DEFAULT 1,
  `isDeleted` int(10) unsigned DEFAULT 0 COMMENT '0 means not deleted, we will copy id in this field to mark record as deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_username_unique` (`username`,`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `repo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL COMMENT 'id from user table',
  `name` varchar(50) DEFAULT NULL COMMENT 'name of repository',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(4) DEFAULT 1,
  `isDeleted` int(10) unsigned DEFAULT 0 COMMENT '0 means not deleted, we will copy id in this field to mark record as deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_repo_unique` (`userId`,`name`,`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `branch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repoId` int(10) unsigned NOT NULL COMMENT 'id from repo table',
  `name` varchar(50) DEFAULT NULL COMMENT 'name of branch',
  `lastCommitSHA` varchar(50) DEFAULT NULL COMMENT 'commit sha for the last commit in the branch',
  `lastCommitDatetime` datetime NOT NULL COMMENT 'datetime when the last commit was done in the branch',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(4) DEFAULT 1,
  `isDeleted` int(10) unsigned DEFAULT 0 COMMENT '0 means not deleted, we will copy id in this field to mark record as deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `repo_branch_unique` (`repoId`,`name`,`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `commit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branchId` int(10) unsigned NOT NULL COMMENT 'id from branch table',
  `commitSHA` varchar(50) DEFAULT NULL COMMENT 'Unique SHA of the commit',
  `authorName` varchar(50) DEFAULT NULL COMMENT 'name of the auther of the repo',
  `authorEmail` varchar(200) DEFAULT NULL COMMENT 'email of the auther of the repo',
  `committerName` varchar(50) DEFAULT NULL COMMENT 'name of the commiter',
  `committerEmail` varchar(200) DEFAULT NULL COMMENT 'email of the commiter',
  `commitDatetime` datetime NOT NULL COMMENT 'datetime when the commit was done',
  `commitMessage` varchar(200) DEFAULT NULL COMMENT 'commit message',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(4) DEFAULT 1,
  `isDeleted` int(10) unsigned DEFAULT 0 COMMENT '0 means not deleted, we will copy id in this field to mark record as deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `commit_commitSHA_unique` (`commitSHA`,`isDeleted`),
  KEY `commitDatetimeIdx` (`commitDatetime`),
  KEY `authorNameIdx` (`authorName`),
  KEY `authorEmailIdx` (`authorEmail`),
  KEY `committerNameIdx` (`committerName`),
  KEY `committerEmailIdx` (`committerEmail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `fetchProcess` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branchId` int(10) unsigned NOT NULL COMMENT 'id from branch table',
  `commitSHA` varchar(50) DEFAULT NULL COMMENT 'Unique SHA of the commit for which we want to fetch all commits after it',
  `nextPageNumber` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'The pageNumber for which we need to fetch commits. We will keep updating it when fetching commits.',
  `status` tinyint(4) DEFAULT '0' COMMENT '0: pending, 1: in-progress, 2: done, 3: failed. It will move like 0->1->0->1....0->1->2',
  `comment` varchar(100) DEFAULT NULL COMMENT 'Comment about the last execution',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(4) DEFAULT '1',
  `isDeleted` int(10) unsigned DEFAULT '0' COMMENT '0 means not deleted, we will copy id in this field to mark record as deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_commitSHA_unique` (`branchId`,`commitSHA`,`isDeleted`),
  KEY `statusIdx` (`status`),
  KEY `branchIdIdx` (`branchId`),
  KEY `createdAtIdx` (`createdAt`),
  KEY `updatedAtIdx` (`updatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `fetchProcessCommitMapping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fetchProcessId` int(10) unsigned NOT NULL COMMENT 'id from branch table',
  `commitId` int(10) unsigned NOT NULL COMMENT 'id from commit table',
  `pageNumber` int(10) unsigned NOT NULL COMMENT 'pageNumber in which this commit was fetched.',
  `status` tinyint(4) DEFAULT 0 COMMENT '0: pending, 1: in-progress, 2: done. It will move like 0->1->0->1....0->1->2',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(4) DEFAULT 1,
  `isDeleted` int(10) unsigned DEFAULT 0 COMMENT '0 means not deleted, we will copy id in this field to mark record as deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fetchProcess_commit_unique` (`fetchProcessId`,`commitId`,`isDeleted`),
  KEY `commitIdIdx` (`commitId`),
  KEY `createdAtIdx` (`createdAt`),
  KEY `updatedAtIdx` (`updatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
