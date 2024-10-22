CREATE TABLE `users` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `date` datetime NOT NULL,
     `updated` datetime NOT NULL,
     `companyId` int(11) DEFAULT NULL,
     `login` varchar(255) DEFAULT NULL,
     `email` varchar(255) NOT NULL,
     `password` varchar(60) DEFAULT NULL,
     `isActive` tinyint(1) DEFAULT '1',
     `autoLoginToken` varchar(40) DEFAULT NULL,
     `autoLoginIdentifier` varchar(40) DEFAULT NULL,
     `lastLogin` datetime DEFAULT NULL,
     `access` varchar(45) NOT NULL DEFAULT 'User',
     `gender` varchar(16) NOT NULL DEFAULT 'Male',
     `firstName` varchar(255) DEFAULT NULL,
     `lastName` varchar(255) DEFAULT NULL,
     `state` varchar(45) DEFAULT 'Created',
     `config` text,
     `initials` varchar(8) DEFAULT NULL,
     `lastClick` datetime DEFAULT NULL,
     `mobile` varchar(255) DEFAULT NULL,
     `phone` varchar(255) DEFAULT NULL,
     `street` varchar(255) DEFAULT NULL,
     `zipcode` varchar(45) DEFAULT NULL,
     `city` varchar(255) DEFAULT NULL,
     `country` varchar(255) DEFAULT NULL,
     `dateOfBirth` date DEFAULT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users_roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` datetime NOT NULL,
    `updated` datetime NOT NULL,
    `title` varchar(45) NOT NULL,
    `roleKey` varchar(45) NOT NULL,
    `orderId` int(11) DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users_2_roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` datetime NOT NULL,
    `updated` datetime NOT NULL,
    `userId` int(11) NOT NULL,
    `roleId` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users_signedactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` datetime NOT NULL,
    `updated` datetime NOT NULL,
    `userId` int(11) NOT NULL,
    `token` varchar(32) NOT NULL,
    `action` varchar(45) NOT NULL,
    `payload` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` datetime NOT NULL,
    `updated` datetime NOT NULL,
    `userId` int(11) DEFAULT NULL,
    `forCompanyId` int(11) DEFAULT NULL,
    `forUserId` int(11) DEFAULT NULL,
    `action` varchar(255) NOT NULL,
    `payload` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `migrations` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` datetime NOT NULL,
      `updated` datetime NOT NULL,
      `userId` int(11) NOT NULL,
      `version` varchar(45) NOT NULL,
      `log` text,
      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `api_clients` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `date` datetime NOT NULL,
       `updated` datetime NOT NULL,
       `tenantId` int(11) NOT NULL,
       `userId` int(11) NOT NULL,
       `title` varchar(45) NOT NULL,
       `clientId` varchar(16) NOT NULL,
       `clientSecret` varchar(64) NOT NULL,
       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
