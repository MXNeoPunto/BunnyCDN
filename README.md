# Integración de Bunny CDN (bunny.net)

Este plugin permite sincronizar automáticamente las imágenes subidas a tu sitio web con Bunny CDN (anteriormente BunnyCDN, ahora bunny.net). Optimiza la entrega de contenido y reduce la carga en tu servidor.

## ¿Qué es Bunny CDN / bunny.net?

Bunny CDN es un servicio de red de entrega de contenido (CDN) rápido y económico. La empresa se ha renombrado a **bunny.net**, pero ambos nombres se refieren al mismo servicio. Este plugin utiliza la API de almacenamiento de bunny.net para subir tus archivos.

## Características

*   **Subida Automática:** Las imágenes se suben automáticamente a Bunny Storage al ser cargadas en el panel de administración.
*   **Conversión WebP:** Convierte imágenes (JPG, PNG) a formato WebP para una carga más rápida (si está habilitado).
*   **Gestión de Archivos:** Opción para mantener o eliminar los archivos locales después de la subida.
*   **Soporte Regional:** Soporte para múltiples regiones de almacenamiento (Falkenstein, New York, Los Angeles, Singapore, Sydney).

## Configuración

Para utilizar este plugin, necesitas una cuenta en [bunny.net](https://bunny.net).

1.  Ve a la sección de configuración del plugin en tu panel de administración.
2.  Completa los siguientes campos:

    *   **API Key (Storage Password):** Esta es la contraseña de tu Storage Zone.
        *   Ve a tu panel de bunny.net -> Storage -> Tu Zona -> FTP & API Access -> **Password** (Puede ser Read-Only o Full Access, se recomienda Full Access para subir/borrar).
        *   **Nota:** No uses tu API Key general de la cuenta, usa la **Password** específica de la Storage Zone.

    *   **Storage Zone Name:** El nombre de tu zona de almacenamiento.
        *   Ejemplo: si tu hostname es `mi-zona.b-cdn.net`, el nombre de la zona suele ser `mi-zona`.
        *   **Importante:** Introduce solo el nombre, no la URL completa.

    *   **Pull Zone URL (Endpoint):** La URL pública desde donde se servirán tus archivos.
        *   Ejemplo: `https://mi-zona.b-cdn.net`
        *   Asegúrate de incluir `https://` y no poner una barra al final.

    *   **Región de Almacenamiento:** Selecciona la región donde creaste tu Storage Zone.
        *   Falkenstein (DE) es la predeterminada.
        *   Si tu zona está en NY, selecciona New York, etc.

    *   **Mantener archivos locales:** Marca esta casilla si deseas conservar una copia de las imágenes en tu servidor original. Si la desmarcas, las imágenes se borrarán del servidor local una vez subidas a la CDN para ahorrar espacio.

## Solución de Problemas (Troubleshooting)

### "Error al subir" o Fallo en la subida

Si encuentras errores al subir archivos:

1.  **Verificar Credenciales:** Asegúrate de que la "API Key" sea la **Storage Zone Password** y no la API Key de tu cuenta principal.
2.  **Nombre de la Zona:** Asegúrate de que el "Storage Zone Name" no contenga espacios ni barras (`/`). Debe ser solo el nombre.
3.  **Región Correcta:** Si tu zona de almacenamiento está en una región diferente a la seleccionada en la configuración, la subida fallará.
4.  **Permisos de Archivo:** Asegúrate de que el servidor tenga permisos de lectura sobre los archivos temporales de subida.
5.  **Logs de Error:** El plugin ahora registra errores detallados en el log de errores de PHP. Revisa el log de errores de tu servidor si el problema persiste.

### Las imágenes no cargan

1.  Verifica la **Pull Zone URL**. Debe ser la URL correcta proporcionada por bunny.net.
2.  Asegúrate de que los archivos se hayan subido correctamente revisando el panel de control de bunny.net.

## Requisitos

*   PHP 7.4 o superior.
*   Extensión cURL habilitada.
*   Extensión GD (para conversión WebP).
