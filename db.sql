-- ════════════════════════════════════════════════════════
-- db.sql · AgendaWeb · tablas "categorias" y "eventos"
--
-- Se ejecuta DENTRO de tu base de datos (no la crea):
--   Terminal:   mysql -u root -p agenda2 < db.sql
--   Workbench:  escribe  USE agenda2;  pega todo debajo y ejecuta con el rayo ⚡
--   DomCloud:   la receta lo ejecuta sola
--
-- Se puede ejecutar varias veces: no borra ni duplica nada.
-- ════════════════════════════════════════════════════════

-- 1. Categorías: cada una se guarda UNA sola vez
CREATE TABLE IF NOT EXISTS categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERT IGNORE: si la categoría ya existe, la salta en lugar de dar error
INSERT IGNORE INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'),
  (2, 'Personal'),
  (3, 'Estudio'),
  (4, 'Ocio / Deporte');

-- 2. Eventos: cada evento guarda el NÚMERO de su categoría (llave foránea)
CREATE TABLE IF NOT EXISTS eventos (
  id           INT AUTO_INCREMENT PRIMARY KEY,   -- número único de cada evento
  titulo       VARCHAR(120) NOT NULL,            -- obligatorio
  fecha        DATE         NOT NULL,            -- obligatorio
  hora         TIME         NULL,                -- opcional
  categoria_id INT          NOT NULL,            -- obligatorio: id de la tabla categorias
  descripcion  VARCHAR(500) NULL,                -- opcional
  creado_en    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eventos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON DELETE RESTRICT                           -- no deja borrar una categoría que tiene eventos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
