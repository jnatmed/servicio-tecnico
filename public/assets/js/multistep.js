document.addEventListener("DOMContentLoaded", () => {
    const step1 = document.getElementById("step1");
    const step2 = document.getElementById("step2");
    const nextStep = document.getElementById("nextStep");
    const prevStep = document.getElementById("prevStep");

    // Mostrar datos en la vista previa
    function populatePreview() {
        const nroFacturaPreview = document.getElementById('preview_nro_factura');
        const fechaFacturaPreview = document.getElementById('preview_fecha_factura');
        const dependenciaPreview = document.getElementById('preview_dependencia');
        const condicionVentaPreview = document.getElementById('previewCondicionVenta');
        const condicionImpositivaPreview = document.getElementById('previewCondicionImpositiva');
        const totalFacturadoPreview = document.getElementById('preview_total_facturado');
        const agentePreview = document.getElementById('preview_agente');

        if (nroFacturaPreview) nroFacturaPreview.textContent = document.getElementById('nro_de_factura').value || 'N/A';
        if (fechaFacturaPreview) fechaFacturaPreview.textContent = document.getElementById('fecha_factura').textContent || 'N/A';
        if (dependenciaPreview) dependenciaPreview.textContent = document.getElementById('dependencia').value || 'N/A';
        if (condicionVentaPreview) condicionVentaPreview.textContent = document.getElementById('condicion_venta').value || 'N/A';
        if (condicionImpositivaPreview) condicionImpositivaPreview.textContent = document.getElementById('condicion_impositiva').value || 'N/A';
        if (totalFacturadoPreview) totalFacturadoPreview.textContent = document.getElementById('total_facturado').textContent || 'N/A';
        if (agentePreview) agentePreview.textContent = document.getElementById('selectedAgent').textContent || 'Ninguno';

        let previewProductosList = document.getElementById('preview_productos');

        // Renderizar tabla de productos en la vista previa
        previewProductosList.innerHTML = `
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        `;   
        // Seleccionar el tbody dentro de la tabla recién creada
        let previewProductosTableBody = previewProductosList.querySelector("tbody");
        // Lista global para almacenar productos
        let productos = [];
        // Agregar las filas de productos al tbody
        productos.forEach((item) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${item.product}</td>
                <td>${item.cantidad}</td>
            `;
            previewProductosTableBody.appendChild(row);
        });        
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
