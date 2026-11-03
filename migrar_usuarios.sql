-- Ejecuta este archivo una sola vez en la base agenda2 para actualizar una instalación existente.
USE agenda2;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Copia de seguridad previa de eventos; no borra ni modifica la tabla original.
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

-- Conserva los eventos existentes como registros antiguos sin propietario.
ALTER TABLE eventos ADD COLUMN usuario_id INT NULL;
ALTER TABLE eventos
  ADD CONSTRAINT fk_eventos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;
