-- ========================================
-- AJOUTER LES SESSIONS UTILISATEURS
-- ========================================

-- Créer les sessions utilisateurs avec la colonne last_activity requise
INSERT INTO user_sessions (user_id, session_token, ip_address, last_activity, expires_at, is_active, created_at) VALUES
                                                                                                                     (22, 'sess_admin_abc123def456', '192.168.1.50', '2025-01-23 15:45:00', '2025-01-24 15:45:00', true, '2025-01-23 15:45:00'),
                                                                                                                     (23, 'sess_agent1_ghi789jkl012', '192.168.1.100', '2025-01-23 16:20:00', '2025-01-24 16:20:00', true, '2025-01-23 16:20:00'),
                                                                                                                     (24, 'sess_agent2_mno345pqr678', '192.168.1.75', '2025-01-22 17:45:00', '2025-01-23 17:45:00', false, '2025-01-22 17:45:00'),
                                                                                                                     (25, 'sess_agent3_stu901vwx234', '192.168.1.101', '2025-01-20 18:00:00', '2025-01-21 18:00:00', false, '2025-01-20 18:00:00');

-- Ajouter quelques paiements supplémentaires pour enrichir les données
INSERT INTO payments (created_by_id, enrollment_id, amount, payment_date, payment_type, payment_method, description, reference, is_active, created_at, updated_at)
SELECT
    23, -- agent1
    e.id,
    950.00,
    '2025-01-22 14:00:00',
    'PARTIAL',
    'BANK_TRANSFER',
    'Deuxième versement Marketing Digital - Leila',
    'PAY-2025-000005',
    true,
    '2025-01-22 14:00:00',
    '2025-01-22 14:00:00'
FROM enrollments e
         JOIN students s ON e.student_id = s.id
         JOIN formations f ON e.formation_id = f.id
WHERE s.cin = '23456789' AND f.code = 'MKT-DIG';

-- Ajouter le reçu correspondant
INSERT INTO receipts (payment_id, generated_by_id, receipt_number, student_name, formation_name, amount, payment_date, payment_type, receipt_content, pdf_path, generated_at, printed_count, last_printed_at)
SELECT
    p.id,
    23, -- agent1
    'REC-2025-005',
    'Leila Trabelsi',
    'Marketing Digital',
    950.00,
    '2025-01-22',
    'PARTIAL',
    'Reçu du deuxième versement pour la formation Marketing Digital',
    '/receipts/REC-2025-005.pdf',
    '2025-01-22 14:15:00',
    0,
    NULL
FROM payments p
WHERE p.reference = 'PAY-2025-000005';

-- Mettre à jour le statut de paiement de Leila (maintenant complètement payée)
UPDATE enrollments SET
                       total_paid = 1900.00,
                       remaining_amount = 0.00,
                       payment_status = 'FULLY_PAID'
WHERE id = (
    SELECT e.id FROM enrollments e
                         JOIN students s ON e.student_id = s.id
                         JOIN formations f ON e.formation_id = f.id
    WHERE s.cin = '23456789' AND f.code = 'MKT-DIG'
);

-- Mettre à jour l'échéance de paiement de Leila
UPDATE payment_installments SET
                                paid_date = '2025-01-22',
                                status = 'PAID'
WHERE installment_number = 2
  AND enrollment_id = (
    SELECT e.id FROM enrollments e
                         JOIN students s ON e.student_id = s.id
                         JOIN formations f ON e.formation_id = f.id
    WHERE s.cin = '23456789' AND f.code = 'MKT-DIG'
);

-- Ajouter quelques logs d'audit (si la table existe)
INSERT INTO audit_logs (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
SELECT
    22,
    'CREATE',
    'payments',
    31,
    '{}',
    '{"amount": 2650.00, "payment_type": "FULL", "student": "Ahmed Ben Salem"}',
    '192.168.1.50',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    '2025-01-15 10:30:00'
    WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'audit_logs');

INSERT INTO audit_logs (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
SELECT
    23,
    'CREATE',
    'enrollments',
    43,
    '{}',
    '{"student": "Leila Trabelsi", "formation": "Marketing Digital", "total_amount": 1900.00}',
    '192.168.1.100',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    '2025-01-16 11:30:00'
    WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'audit_logs');

-- Ajouter des données dans statistics_cache (si la table existe)
INSERT INTO statistics_cache (key, value, expires_at, created_at, updated_at)
SELECT
    'total_students_active',
    '7',
    '2025-01-24 00:00:00',
    '2025-01-23 00:00:00',
    '2025-01-23 00:00:00'
    WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'statistics_cache')
AND NOT EXISTS (SELECT 1 FROM statistics_cache WHERE key = 'total_students_active');

INSERT INTO statistics_cache (key, value, expires_at, created_at, updated_at)
SELECT
    'total_revenue_month',
    '5963.33',
    '2025-02-01 00:00:00',
    '2025-01-23 00:00:00',
    '2025-01-23 00:00:00'
    WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'statistics_cache')
AND NOT EXISTS (SELECT 1 FROM statistics_cache WHERE key = 'total_revenue_month');

INSERT INTO statistics_cache (key, value, expires_at, created_at, updated_at)
SELECT
    'enrollments_pending',
    '1',
    '2025-01-24 00:00:00',
    '2025-01-23 00:00:00',
    '2025-01-23 00:00:00'
    WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'statistics_cache')
AND NOT EXISTS (SELECT 1 FROM statistics_cache WHERE key = 'enrollments_pending');

-- ========================================
-- VÉRIFICATIONS FINALES ET STATISTIQUES COMPLÈTES
-- ========================================

SELECT '=== RÉSUMÉ COMPLET DES DONNÉES ===' as info;

-- Compter tous les enregistrements
SELECT 'ACADEMIC_LEVELS' as table_name, COUNT(*) as count FROM academic_levels
UNION ALL
SELECT 'USERS', COUNT(*) FROM users
UNION ALL
SELECT 'FORMATIONS', COUNT(*) FROM formations
UNION ALL
SELECT 'CLASSES', COUNT(*) FROM classes
UNION ALL
SELECT 'PAYMENT_MODES', COUNT(*) FROM payment_modes
UNION ALL
SELECT 'STUDENTS', COUNT(*) FROM students
UNION ALL
SELECT 'ENROLLMENTS', COUNT(*) FROM enrollments
UNION ALL
SELECT 'PAYMENTS', COUNT(*) FROM payments
UNION ALL
SELECT 'RECEIPTS', COUNT(*) FROM receipts
UNION ALL
SELECT 'PAYMENT_INSTALLMENTS', COUNT(*) FROM payment_installments
UNION ALL
SELECT 'USER_SESSIONS', COUNT(*) FROM user_sessions
UNION ALL
SELECT 'AUDIT_LOGS', CASE WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'audit_logs')
                              THEN (SELECT COUNT(*) FROM audit_logs)::text
                          ELSE 'N/A' END
UNION ALL
SELECT 'STATISTICS_CACHE', CASE WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'statistics_cache')
                                    THEN (SELECT COUNT(*) FROM statistics_cache)::text
                                ELSE 'N/A' END;

-- Statistiques détaillées
SELECT '=== STATISTIQUES DÉTAILLÉES ===' as info;

SELECT 'Total des revenus' as stat_name, SUM(amount)::text || ' DT' as value
FROM payments WHERE is_active = true
UNION ALL
SELECT 'Étudiants actifs', COUNT(*)::text
FROM students WHERE is_active = true
UNION ALL
SELECT 'Inscriptions confirmées', COUNT(*)::text
FROM enrollments WHERE registration_status = 'CONFIRMED'
UNION ALL
SELECT 'Paiements complets', COUNT(*)::text
FROM enrollments WHERE payment_status = 'FULLY_PAID'
UNION ALL
SELECT 'Paiements partiels', COUNT(*)::text
FROM enrollments WHERE payment_status = 'PARTIAL'
UNION ALL
SELECT 'Paiements en attente', COUNT(*)::text
FROM enrollments WHERE payment_status = 'NOT_PAID'
UNION ALL
SELECT 'Reçus imprimés', COUNT(*)::text
FROM receipts WHERE printed_count > 0
UNION ALL
SELECT 'Sessions actives', COUNT(*)::text
FROM user_sessions WHERE is_active = true AND expires_at > NOW()
UNION ALL
SELECT 'Formations actives', COUNT(*)::text
FROM formations WHERE is_active = true
UNION ALL
SELECT 'Classes avec étudiants', COUNT(*)::text
FROM classes WHERE current_students > 0;

-- Vue d'ensemble par formation
SELECT '=== INSCRIPTIONS PAR FORMATION ===' as info;

SELECT
    f.name as formation,
    COUNT(e.id) as inscriptions,
    SUM(e.total_amount) as revenus_potentiels,
    SUM(e.total_paid) as revenus_realises,
    SUM(e.remaining_amount) as reste_a_payer
FROM formations f
         LEFT JOIN enrollments e ON f.id = e.formation_id
WHERE f.is_active = true
GROUP BY f.id, f.name
ORDER BY inscriptions DESC;

-- État des paiements par étudiant
SELECT '=== ÉTAT DES PAIEMENTS PAR ÉTUDIANT ===' as info;

SELECT
    s.first_name || ' ' || s.last_name as etudiant,
    f.name as formation,
    e.total_amount as montant_total,
    e.total_paid as montant_paye,
    e.remaining_amount as reste_a_payer,
    e.payment_status as statut,
    COUNT(p.id) as nb_paiements
FROM students s
         JOIN enrollments e ON s.id = e.student_id
         JOIN formations f ON e.formation_id = f.id
         LEFT JOIN payments p ON e.id = p.enrollment_id
WHERE s.is_active = true
GROUP BY s.id, s.first_name, s.last_name, f.name, e.total_amount, e.total_paid, e.remaining_amount, e.payment_status
ORDER BY s.last_name, s.first_name;

SELECT '=== DONNÉES DE TEST CRÉÉES AVEC SUCCÈS ! ===' as success;
