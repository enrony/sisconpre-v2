INSERT INTO `payment_reports_movements_estatus` (`id`, `description`, `action_description`, `style`, `finish_estatus`, `motivo`, `soporte`, `grupos_trabajos_user_id`, `estatus`, `created_at`, `updated_at`) VALUES
	(1, 'Pendiente', 'Pendiente', 'text-gray-400', 0, 0, 0, NULL, 1, '2024-05-16 05:10:12', '2024-05-16 05:10:12'),
	(2, 'Aprobado', 'Aprobar', 'text-green-400', 1, 0, 0, NULL, 1, '2024-05-16 05:10:12', '2024-05-16 05:10:12'),
	(3, 'Rechazado', 'Rechazar', 'text-red-400', 1, 1, 0, NULL, 1, '2024-05-16 05:10:12', '2024-05-16 05:10:12'),
	(4, 'En revisión', 'Pasar a revisión', 'text-yellow-400', 0, 1, 0, NULL, 1, '2024-05-16 05:10:12', '2024-05-16 05:10:12'),
	(5, 'Remitido', 'Remitir', 'text-yellow-800', 1, 1, 0, NULL, 1, '2024-05-16 05:10:12', '2024-05-16 05:10:12');
