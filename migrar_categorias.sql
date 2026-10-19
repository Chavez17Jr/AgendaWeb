-- ════════════════════════════════════════════════════════
-- migrar_categorias.sql · SOLO si ya tenías la tabla "eventos" vieja,
-- con la categoría escrita como texto (columna "categoria").
--
-- Primero ejecuta db.sql (crea la tabla "categorias").
-- En DomCloud la receta lo ejecuta sola cuando hace falta.
-- ════════════════════════════════════════════════════════

-- Paso 1. Columna nueva, todavía vacía
ALTER TABLE eventos ADD COLUMN categoria_id INT NULL AFTER categoria;

-- Paso 2. Traducir el texto viejo al número de su categoría
UPDATE eventos SET categoria_id = 1 WHERE categoria = 'trabajo';
UPDATE eventos SET categoria_id = 2 WHERE categoria = 'personal';
UPDATE eventos SET categoria_id = 3 WHERE categoria = 'estudio';
UPDATE eventos SET categoria_id = 4 WHERE categoria = 'ocio';

-- Paso 3. Verificar: debe dar 0. Si no, el paso 4 falla y la columna vieja
-- se queda como estaba (no se pierde nada).
SELECT COUNT(*) AS sin_traducir FROM eventos WHERE categoria_id IS NULL;

-- Paso 4. Volverla obligatoria y ponerle la llave foránea
ALTER TABLE eventos
  MODIFY categoria_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE RESTRICT;

-- Paso 5. Ya con todo comprobado, borrar la columna vieja
ALTER TABLE eventos DROP COLUMN categoria;
