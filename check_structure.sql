-- Vérifier les données existantes et la structure

-- 1. Voir la structure exacte de chaque table
\d academic_levels
\d users
\d formations
\d classes
\d payment_modes
\d students

-- 2. Voir les données déjà présentes
SELECT 'ACADEMIC_LEVELS existants:' as info;
SELECT * FROM academic_levels ORDER BY id;

SELECT 'USERS existants:' as info;
SELECT id, login, role, full_name FROM users ORDER BY id;

SELECT 'FORMATIONS existantes:' as info;
SELECT id, name, academic_level_id FROM formations ORDER BY id;

SELECT 'PAYMENT_MODES existants:' as info;
SELECT id, name, code FROM payment_modes ORDER BY id;

SELECT 'STUDENTS existants:' as info;
SELECT id, cin, first_name, last_name FROM students ORDER BY id;

SELECT 'CLASSES existantes:' as info;
SELECT id, name, formation_id FROM classes ORDER BY id;
