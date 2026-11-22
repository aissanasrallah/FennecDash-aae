<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'goals')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "goals` (
  `id` int(11) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `goal_type` int(11) NOT NULL,
  `recurrence_type` varchar(20) NOT NULL DEFAULT 'one_time',
  `recurrence_interval` int(11) NOT NULL DEFAULT 1,
  `custom_period_days` int(11) DEFAULT NULL,
  `attribution_mode` varchar(20) NOT NULL DEFAULT 'individual',
  `series_active` tinyint(1) NOT NULL DEFAULT 1,
  `contract_type` int(11) NOT NULL DEFAULT '0',
  `achievement` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notify_when_fail` tinyint(1) NOT NULL DEFAULT '1',
  `notify_when_achieve` tinyint(1) NOT NULL DEFAULT '1',
  `notified` int(11) NOT NULL DEFAULT '0',
  `staff_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'goal_staff')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'goal_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `removed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_goal_staff` (`goal_id`, `staff_id`),
  KEY `idx_goal_staff_lookup` (`staff_id`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'goal_periods')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'goal_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL,
  `period_key` varchar(32) NOT NULL,
  `period_label` varchar(120) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `target_achievement` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_historical` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_goal_period` (`goal_id`, `period_key`),
  KEY `idx_goal_period_dates` (`goal_id`, `period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}
