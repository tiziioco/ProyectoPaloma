# ProyectoPaloma

## Descripción del Proyecto
**MedStock** es un sistema de software funcional diseñado para combatir la falta de adherencia a los tratamientos médicos y el agotamiento inesperado de fármacos. A diferencia de una página puramente informativa, este sistema procesa activamente los datos de inventario doméstico mediante un **control dinámico de caducidad**, clasifica los productos por prioridad (estilo semáforo) y automatiza el flujo de reposición calculando cuándo se agotarán las existencias para emitir alertas preventivas.

El proyecto cuenta con un enfoque social y de salud crítico, permitiendo además un **Modo Cuidador** para que terceros (familiares o personal médico) supervisen de manera remota el cumplimiento de las tomas y el stock de pacientes a cargo.

---

##  Funcionalidades Clave
*   **Inventario Automatizado (CRUD):** Registro detallado de medicamentos, dosis, contenido total y fechas de vencimiento.
*   **Descuento de Stock en Tiempo Real:** El sistema resta automáticamente la dosis indicada del inventario total cada vez que el usuario presiona "Confirmar toma".
*   **Alertas de Reposición Predictivas:** Notificaciones automáticas cuando el `stock_actual` cruza el umbral del `stock_minimo`.
*   **Modo Cuidador Integrado:** Relación jerárquica de usuarios que permite a un tutor supervisar las tomas y recibir duplicados de las alertas de stock crítico.
*   **Dashboard de Cumplimiento:** Gráficos estadísticos mensuales sobre el nivel de cumplimiento del tratamiento.

---

##  Arquitectura de Datos (DER)
El sistema se apoya en una base de datos relacional optimizada de **4 tablas**:

1.  **USUARIO:** Gestiona las credenciales, los roles (Paciente/Cuidador) y la jerarquía interna mediante recursividad (`id_superior`).
2.  **MEDICAMENTO:** Almacena la información de los fármacos, el `stock_actual` y el `stock_minimo`.
3.  **PROGRAMACION:** Define el plan de toma (dosis, frecuencia horaria y cálculo de la próxima toma).
4.  **HISTORIAL_TOMAS:** Registra de forma histórica cada evento de toma confirmado u omitido.

---

##  Stack Tecnológico Sugerido
*   **Backend:** Node.js / Python (Flask o Django)
*   **Frontend:** HTML5, CSS3, JavaScript (o React para una interfaz SPA interactiva)
*   **Base de Datos:** SQLite o MySQL

---

##  Integrantes (Grupo de 4)
*   *Integrante 1 Tiziano Giacomozzi
*   *Integrante 2 Sebastian Quintero
*   *Integrante 3 Leonel Tello
*   *Integrante 4 Ian Vecchio

**Desarrollado bajo la firma de la empresa estudiantil:** `Medicstore Software` 

