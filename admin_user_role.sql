CREATE TABLE `tenants` (
    `tenant_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT
);

CREATE TABLE `users` (
    `user_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `tenant_id` BIGINT,
    `role_id` BIGINT,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`tenant_id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE SET NULL
);

CREATE TABLE `roles` (
    `role_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL
);

CREATE TABLE `permissions` (
    `permission_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `permission_name` VARCHAR(50) NOT NULL
);

CREATE TABLE `role_permissions` (
    `role_id` BIGINT,
    `permission_id` BIGINT,
    `access` BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE
);
