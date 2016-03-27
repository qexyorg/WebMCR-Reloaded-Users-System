INSERT INTO `mcr_permissions` (`title`, `description`, `value`, `system`, `type`, `default`, `data`) VALUES
('Доступ к списку пользователей', 'Доступ к просмотру списка пользователей', 'mod_users_list', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Доступ к информации о пользователе', 'Доступ к просмотру полной информации о пользователе', 'mod_users_full', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Доступ к списку комментариев пользователя', 'Доступ к просмотру комментариев в профиле пользователя', 'mod_users_comments', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Добавление комментариев в профилях пользователей', 'Доступ к добавлению комментариев в профилях пользователей', 'mod_users_comment_add', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Доступ к удалению собственных комментариев в пользователях', 'Доступ к удалению собственных комментариев в профилях пользователей', 'mod_users_comment_del', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Доступ к удалению всех комментариев в пользователях', 'Доступ к удалению всех комментариев в профилях пользователей', 'mod_users_comment_del_all', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Доступ к настройкам модуля пользователей в ПУ', 'Доступ к настройкам модуля пользователей в панели управления', 'mod_users_adm_settings', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}'),
('Пункт меню "Модуль пользователей"', 'Доступ к пункту меню "Модуль пользователей" в панели управления.', 'mod_adm_m_i_us', 0, 'boolean', 'false', '{"time_create":1005553535,"time_last":1005553535,"login_create":"admin","login_last":"admin"}');
#line
CREATE TABLE IF NOT EXISTS `mod_users_comments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `from` int(10) NOT NULL,
  `text_bb` text NOT NULL,
  `text_html` text NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
#line