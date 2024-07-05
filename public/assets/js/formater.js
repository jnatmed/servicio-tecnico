document.addEventListener('DOMContentLoaded', () => {

    // Obtener el elemento del DOM
    const h3Element = document.querySelector('.tipo-nro');

    // Verificar si el elemento h3 está definido
    if (h3Element) {
        // Obtener el número de orden del texto actual del h3
        const currentText = h3Element.textContent.trim();

        // Extraer solo el número de orden eliminando el prefijo y cualquier espacio adicional
        const currentId = currentText.replace('Orden de Trabajo Nro # ', '').trim();

        // Verificar si el número de orden actual es un número válido
        if (!isNaN(currentId)) {
            // Formatear el número de orden con cuatro dígitos
            const formattedId = currentId.padStart(4, '0');
            h3Element.textContent = `Orden de Trabajo Nro # ${formattedId}`;
        } else {
            // Si no es un número válido, mostrar un mensaje alternativo
            h3Element.textContent = 'Orden de Trabajo Nro Desconocido';
        }
    } else {
        console.error('No se encontró el elemento .h3-orden-de-trabajo en el DOM.');
    }



    // Obtener todos los elementos del DOM con la clase 'tipo-nro-lista'
    const tipoNroListas = document.querySelectorAll('.tipo-nro-lista');

    // Iterar sobre cada elemento y aplicar la lógica
    tipoNroListas.forEach(tipoNroLista => {
        // Obtener el número de orden del texto actual del elemento
        const currentText = tipoNroLista.textContent.trim();

        // Extraer solo el número de orden eliminando el prefijo y cualquier espacio adicional
        const currentId = currentText.trim();

        // Verificar si el número de orden actual es un número válido
        if (!isNaN(currentId) && currentId !== '') {
            // Formatear el número de orden con cuatro dígitos
            const formattedId = currentId.padStart(4, '0');
            tipoNroLista.textContent = `# ${formattedId}`;
        } else {
            // Si no es un número válido, mostrar un mensaje alternativo
            tipoNroLista.textContent = 's/n';
        }
    });

});
