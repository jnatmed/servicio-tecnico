document.addEventListener("DOMContentLoaded", () => {
    const step1 = document.getElementById("step1");
    const step2 = document.getElementById("step2");
    const nextStep = document.getElementById("nextStep");
    const prevStep = document.getElementById("prevStep");

    // Mostrar datos en la vista previa
    function populatePreview() {
        const fechaFacturaPreview = document.getElementById('preview_fecha_factura');
        const dependenciaPreview = document.getElementById('preview_dependencia');
        const condicionVentaPreview = document.getElementById('previewCondicionVenta');
        const condicionImpositivaPreview = document.getElementById('previewCondicionImpositiva');
        const totalFacturadoPreview = document.getElementById('preview_total_facturado');
        const agentePreview = document.getElementById('preview_agente');

        const dependenciaSelect = document.getElementById('dependencia');
        
        /**
         * Si las variables ...Preview fueron inicializadas en el paso 1
         * entonces en el paso 2, van a tenr un valor. 
         */
        // if (nroFacturaPreview) nroFacturaPreview.textContent = document.getElementById('nro_de_factura').value || 'N/A';
        if (fechaFacturaPreview) fechaFacturaPreview.textContent = 'Fecha: ' + document.getElementById('fecha_factura').textContent || 'N/A';
        if (dependenciaPreview) dependenciaPreview.textContent = dependenciaSelect.options[dependenciaSelect.selectedIndex].text || 'N/A';
        if (condicionVentaPreview) condicionVentaPreview.textContent = document.getElementById('condicion_venta').value || 'N/A';
        if (condicionImpositivaPreview) condicionImpositivaPreview.textContent = document.getElementById('condicion_impositiva').value || 'N/A';
        if (totalFacturadoPreview) totalFacturadoPreview.textContent = document.getElementById('total_facturado').textContent || 'N/A';
        if (agentePreview) agentePreview.textContent = document.getElementById('selectedAgent').textContent || 'Ninguno';

        let previewProductosList = document.getElementById('preview_productos');

        // Seleccionar el tbody dentro de la tabla recién creada
        let previewProductosTableBody = previewProductosList.querySelector("tbody");
        // Lista global para almacenar productos
        let productos = [];

        let total = 0;

        
        /**
         * cargo todos los productos seleccionados junto con su descripcion
         */
        document.querySelectorAll("#productosTable tbody tr").forEach(row => {
            let cantidadInput = row.cells[0].querySelector("input"); // Cantidad en la tercera celda
            let descripcion = row.cells[1].textContent.trim(); // Descripcion del Producto en la primera celda
            let nro_proyecto_productivo = row.cells[2].textContent.trim(); // Producto en la primera celda
            let precio_unitario = parseFloat(row.cells[3].textContent.trim()); // Precio en la segunda celda
        
            // Verificar si existe el input de cantidad
            if (!cantidadInput) {
                console.warn("No se encontró input de cantidad en la fila:", row);
                return; // Evitar que el código continúe con valores nulos
            }
        
            let cantidad = parseInt(cantidadInput.value, 10) || 0; // Obtener la cantidad
            let importe = precio_unitario * cantidad; // Calcular el subtotal
        
            // Acumular el total
            total += importe;
        
            // Almacenar el producto y cantidad
            productos.push( { descripcion: descripcion, 
                              nro_proyecto_productivo: nro_proyecto_productivo, 
                              cantidad: cantidad, 
                              precio_unitario: precio_unitario, 
                              importe: importe, 
                              total: total });
        
            // Actualizar el subtotal de la fila
            row.querySelector(".subtotal").textContent = importe.toFixed(2);
        });
        
        
        previewProductosTableBody = document.querySelector("#preview_productos tbody");
        previewProductosTableBody.innerHTML = ""; // Limpiar antes de agregar



        // Agregar las filas de productos al tbody
        productos.forEach((item) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${item.cantidad}</td>
                <td>${item.descripcion}</td>
                <td>${item.nro_proyecto_productivo}</td>
                <td>${item.precio.toFixed(2)}</td>
                <td>${item.importe.toFixed(2)}</td>
            `;
            previewProductosTableBody.appendChild(row);
        });   
        
        
        // Asignar el total facturado calculado
        totalFacturadoPreview.textContent = total.toFixed(2);
        document.getElementById('total_facturado').textContent = total.toFixed(2);
        
    }

    nextStep.addEventListener("click", () => {
        console.log('Avanzando al paso 2'); // Verificar si el evento se dispara
        populatePreview();
        step1.classList.remove("active");
        step1.classList.add("d-none");
        step2.classList.remove("d-none");
        step2.classList.add("active");
    });
    
    prevStep.addEventListener("click", () => {
        console.log('Volviendo al paso 1'); // Verificar si el evento se dispara
        step2.classList.remove("active");
        step2.classList.add("d-none");
        step1.classList.remove("d-none");
        step1.classList.add("active");
    });

    // Inicializar primer paso como activo
    step1.classList.add("active");
});
