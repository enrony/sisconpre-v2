INSERT INTO `prestamos_estatus` (`id`, `description`, `action_description`, `style`, `finish_estatus`, `motivo`, `soporte`, `grupos_trabajos_user_id`, `estatus`, `created_at`, `updated_at`, `type_tag`) VALUES
	(1, 'Pendiente', 'Pendiente', 'text-gray-400', 0, 0, 0, NULL, 1, '2025-01-31 20:58:19', '2025-01-31 20:58:19', '{"type": "info", "color_fondo": null}'),
	(2, 'Pagado', 'Pagado', 'text-green-400', 1, 0, 0, NULL, 1, '2025-01-31 20:58:19', '2025-01-31 20:58:19', '{"type": "success", "color_fondo": null}'),
	(3, 'Anulado', 'Anulado', 'text-yellow-400', 1, 0, 0, NULL, 1, '2025-01-31 20:58:19', '2025-01-31 20:58:19', '{"type": "warning", "color_fondo": null}'),
	(4, 'Perdido', 'Perdido', 'text-red-400', 0, 0, 0, NULL, 1, '2025-01-31 20:58:19', '2025-01-31 20:58:19', '{"type": "danger", "color_fondo": null}');
